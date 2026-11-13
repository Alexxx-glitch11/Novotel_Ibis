<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CancellationRequest;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\TaxServiceRate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    // Status reservasi yang dianggap "terkonfirmasi"
    private const CONFIRMED = ['confirmed', 'partially_arrived', 'checked_in', 'completed'];

    public function index(Request $request)
    {
        $outlets  = Outlet::where('is_active', true)->orderBy('name')->get();
        $selected = $outlets->firstWhere('outlets_id', (int) $request->query('outlet')); // null = semua outlet

        $revenue = $this->revenue($selected);

        return view('manager.dashboard', [
            'outlets'       => $outlets,
            'selected'      => $selected,
            'stats'         => $this->stats($selected, $revenue),
            'cancellations' => $this->cancellations($selected),
            'revenue'       => $revenue['bars'],
            'revenueTotal'  => $this->rupiahShort($revenue['total']),
            'rates'         => $this->rates($selected, $outlets),
            'activities'    => $this->activities(),
        ]);
    }

    private function stats(?Outlet $outlet, array $revenue): array
    {
        $today = Reservation::query()
            ->whereDate('reservation_date', now()->toDateString())
            ->when($outlet, fn (Builder $q) => $q->where('outlets_id', $outlet->outlets_id));

        $confirmed = (clone $today)->whereIn('reservation_status', self::CONFIRMED)->count();
        $waiting   = (clone $today)->where('reservation_status', 'pending_payment')->count();
        $guests    = (int) (clone $today)
            ->whereIn('reservation_status', [...self::CONFIRMED, 'pending_payment'])
            ->sum('guest_count');

        $pendingCancel = $this->pendingCancellations($outlet)->count();

        return [
            [
                'letter' => 'R', 'tone' => 'blue', 'label' => 'Reservasi hari ini',
                'value'  => (string) ($confirmed + $waiting),
                'note'   => "$confirmed terkonfirmasi • $waiting menunggu",
            ],
            [
                'letter' => 'P', 'tone' => 'amber', 'label' => 'Pembatalan menunggu',
                'value'  => (string) $pendingCancel,
                'note'   => $pendingCancel ? 'Perlu keputusan Manager' : 'Tidak ada yang menunggu',
            ],
            [
                'letter' => 'P', 'tone' => 'green', 'label' => 'Pendapatan terverifikasi',
                'value'  => $this->rupiahShort($revenue['total']),
                'note'   => 'Dari ' . $revenue['count'] . ' pembayaran • 7 hari',
            ],
            [
                'letter' => 'T', 'tone' => 'blue', 'label' => 'Tamu dijadwalkan',
                'value'  => (string) $guests,
                'note'   => $outlet ? 'Untuk ' . $outlet->name : 'Untuk seluruh outlet',
            ],
        ];
    }

    private function pendingCancellations(?Outlet $outlet): Builder
    {
        return CancellationRequest::query()
            ->where('status', 'pending')
            ->when($outlet, fn (Builder $q) => $q->whereHas(
                'reservation',
                fn (Builder $r) => $r->where('outlets_id', $outlet->outlets_id)
            ));
    }

    private function cancellations(?Outlet $outlet): array
    {
        return $this->pendingCancellations($outlet)
            ->with('reservation.outlet')
            ->oldest('created_at')
            ->limit(5)
            ->get()
            ->map(function (CancellationRequest $c) {
                $r    = $c->reservation;
                $time = str_replace(':', '.', substr((string) $r->start_time, 0, 5));
                $date = $r->reservation_date->isToday()
                    ? ''
                    : $r->reservation_date->locale('id')->translatedFormat('d M') . ', ';

                return [
                    'guest'  => $r->customer_name,
                    'slot'   => $r->outlet->name . ' • ' . $date . $time,
                    'reason' => Str::limit($c->reason, 40),
                ];
            })
            ->all();
    }

    // Pendapatan dari pembayaran berstatus paid, 7 hari terakhir
    private function revenue(?Outlet $outlet): array
    {
        $from = now()->subDays(6)->startOfDay();

        $paid = fn () => Payment::query()
            ->where('payment_status', 'paid')
            ->whereRaw('COALESCE(verified_at, created_at) >= ?', [$from])
            ->when($outlet, fn (Builder $q) => $q->whereHas(
                'reservation',
                fn (Builder $r) => $r->where('outlets_id', $outlet->outlets_id)
            ));

        $perDay = $paid()
            ->selectRaw('DATE(COALESCE(verified_at, created_at)) as day, SUM(amount) as total')
            ->groupByRaw('DATE(COALESCE(verified_at, created_at))')
            ->pluck('total', 'day');

        $bars  = [];
        $total = 0.0;

        for ($i = 6; $i >= 0; $i--) {
            $date  = now()->subDays($i);
            $value = (float) ($perDay[$date->toDateString()] ?? 0);
            $total += $value;

            $bars[] = [
                'day'   => $date->locale('id')->translatedFormat('D'),
                'value' => $value,
                'label' => $this->rupiahShort($value),
            ];
        }

        return ['bars' => $bars, 'total' => $total, 'count' => $paid()->count()];
    }

    // Tarif yang masih berlaku hari ini, per outlet
    private function rates(?Outlet $selected, $outlets): array
    {
        $today = now()->toDateString();

        return $outlets
            ->when($selected, fn ($c) => $c->where('outlets_id', $selected->outlets_id))
            ->map(function (Outlet $o) use ($today) {
                $rate = TaxServiceRate::query()
                    ->where('outlets_id', $o->outlets_id)
                    ->whereDate('effective_from', '<=', $today)
                    ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $today))
                    ->orderByDesc('effective_from')
                    ->first();

                return [
                    'outlet'  => $o->name,
                    'tax'     => $rate ? $this->percent($rate->tax_rate) : '—',
                    'service' => $rate ? $this->percent($rate->service_rate) : '—',
                ];
            })
            ->values()
            ->all();
    }

    // Aktivitas terbaru dari audit_logs (tabel ini tidak punya kolom outlet, jadi tidak ikut filter)
    private function activities(): array
    {
        $entities = [
            'payment'              => 'Pembayaran',
            'reservation'          => 'Reservasi',
            'cancellation_request' => 'Pembatalan',
            'tax_service_rate'     => 'Tarif',
            'table'                => 'Meja',
            'area'                 => 'Denah',
            'menu_item'            => 'Menu',
            'menu_category'        => 'Kategori menu',
        ];

        $verbs = [
            'created'   => 'dibuat',
            'updated'   => 'diperbarui',
            'deleted'   => 'dihapus',
            'approved'  => 'disetujui',
            'rejected'  => 'ditolak',
            'confirmed' => 'dikonfirmasi',
            'cancelled' => 'dibatalkan',
            'uploaded'  => 'diunggah',
            'verified'  => 'diverifikasi',
            'expired'   => 'kedaluwarsa',
            'requested' => 'diajukan',
        ];

        return AuditLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('audit_logs_id')
            ->limit(5)
            ->get()
            ->map(function (AuditLog $log) use ($entities, $verbs) {
                $at      = Carbon::parse($log->created_at);
                $type    = Str::singular(Str::snake(class_basename((string) $log->entity_type)));
                $entity  = $entities[$type] ?? Str::headline($type);
                $action  = strtolower((string) $log->action);
                $verbKey = Str::afterLast($action, '_');   // reservation_created -> created
                $verb    = $verbs[$verbKey] ?? str_replace('_', ' ', $action);

                return [
                    'time' => $at->isToday() ? $at->format('H.i') : $at->locale('id')->translatedFormat('d M'),
                    'text' => "$entity $verb",
                ];
            })
            ->all();
    }

    private function rupiahShort(float $n): string
    {
        if ($n >= 1_000_000_000) {
            return 'Rp' . number_format($n / 1_000_000_000, 1, ',', '.') . ' M';
        }
        if ($n >= 1_000_000) {
            return 'Rp' . number_format($n / 1_000_000, 1, ',', '.') . ' jt';
        }

        return 'Rp' . number_format($n, 0, ',', '.');
    }

    private function percent($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ',') . '%';
    }
}