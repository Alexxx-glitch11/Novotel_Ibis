<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\AuditLog;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\ReservationQrToken;
use App\Models\ReservationHold;
use App\Models\Table;
use App\Services\ReservationAvailability;
use App\Services\ReservationQrCode;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Str;

class TableSelectionController extends Controller
{
    public function __construct(private readonly ReservationAvailability $availability)
    {
    }

    public function show(Request $request, string $outlet): View|RedirectResponse
    {
        $outlet = $this->findOutlet($outlet);
        $areas = $outlet->areas()->where('is_active', true)->orderBy('name')->get();
        $flow = $request->session()->get($this->flowKey($outlet->code), []);
        if ($request->query('step') === 'tables' && in_array($flow['step'] ?? null, ['menu', 'review'], true)) {
            $this->releaseCurrentHold($request, $outlet, $flow);
            unset($flow['hold_id']);
            $flow['step'] = 'tables';
            $request->session()->put($this->flowKey($outlet->code), $flow);
        }
        $step = $request->query('step', $flow['step'] ?? 'details');
        if (! in_array($step, ['details', 'tables', 'menu', 'review'], true)) {
            $step = 'details';
        }

        if (in_array($step, ['tables', 'menu', 'review'], true)
            && (! isset($flow['reservation_date'], $flow['start_time'], $flow['end_time'], $flow['guest_count']))) {
            $request->session()->forget($this->flowKey($outlet->code));

            return redirect()->route('reservasi.outlet', $outlet->code)
                ->withErrors(['reservation' => __('ui.complete_details_first')]);
        }

        $tablesByArea = collect();
        $hold = null;
        $selectedTable = null;

        if ($step !== 'review') {
            foreach ($areas as $area) {
                $tables = $area->tables()->orderBy('table_code')->get();
                if (isset($flow['reservation_date'], $flow['start_time'], $flow['end_time'], $flow['guest_count'])) {
                    $availableIds = $this->availability->availableTables(
                        $outlet->outlets_id,
                        $area->areas_id,
                        $flow['reservation_date'],
                        $flow['start_time'],
                        $flow['end_time'],
                        (int) $flow['guest_count'],
                    )->modelKeys();
                    $availableIds = array_map('intval', $availableIds);
                } else {
                    $availableIds = $tables->filter(fn (Table $table) => $table->is_active)->modelKeys();
                    $availableIds = array_map('intval', $availableIds);
                }
                $tables->each(fn (Table $table) => $table->setAttribute(
                    'available_for_slot',
                    $table->is_active && in_array((int) $table->tables_id, $availableIds, true),
                ));
                $tablesByArea->put($area->areas_id, $tables);
            }
        }

        if (in_array($step, ['menu', 'review'], true)) {
            $hold = $this->activeFlowHold($request, $outlet, $flow);
            if (! $hold) {
                if (! empty($flow['hold_id'])) {
                    ReservationHold::where('reservation_holds_id', $flow['hold_id'])
                        ->where('session_token_hash', $this->sessionHash($request))
                        ->where('status', 'active')
                        ->update(['status' => 'expired']);
                }
                $flow['step'] = 'tables';
                unset($flow['hold_id']);
                $request->session()->put($this->flowKey($outlet->code), $flow);

                return redirect()->route('reservasi.outlet', ['outlet' => $outlet->code, 'step' => 'tables'])
                    ->withErrors(['hold' => __('ui.hold_expired')]);
            }
            $selectedTable = $hold->tables->first();
        }

        $menuItems = collect();
        $selectedMenuItems = $flow['menu_items'] ?? [];
        if (in_array($step, ['menu', 'review'], true)) {
            $menuItems = $outlet->menuItems()
                ->where('is_available', true)
                ->whereHas('category', fn ($query) => $query->where('is_active', true))
                ->with('category')
                ->orderBy('name')
                ->get();
        }

        if ($step === 'menu') {
            return view('reservasi.menu', compact('outlet', 'flow', 'hold', 'selectedTable', 'menuItems', 'selectedMenuItems'));
        }
        if ($step === 'review') {
            return view('reservasi.review', compact('outlet', 'flow', 'hold', 'selectedTable', 'menuItems', 'selectedMenuItems'));
        }

        return view('reservasi.pilih-meja', compact('outlet', 'areas', 'flow', 'step', 'tablesByArea', 'hold', 'selectedTable'));
    }

    public function findTables(Request $request, string $outlet): RedirectResponse
    {
        $outlet = $this->findOutlet($outlet);
        $today = Carbon::now(config('reservation.timezone'))->toDateString();

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+().\-\s]{8,20}$/'],
            'customer_email' => ['required', 'email', 'max:100'],
            'guest_count' => ['required', 'integer', 'min:1', 'max:50'],
            'reservation_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:' . $today],
            'start_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $timezone = config('reservation.timezone');
        $start = Carbon::createFromFormat('Y-m-d H:i', $data['reservation_date'] . ' ' . $data['start_time'], $timezone);
        if ($start->lessThanOrEqualTo(Carbon::now($timezone))) {
            throw ValidationException::withMessages(['start_time' => __('ui.time_must_be_future')]);
        }

        $end = $start->copy()->addMinutes((int) config('reservation.duration_minutes', 120));
        if (! $end->isSameDay($start)) {
            throw ValidationException::withMessages(['start_time' => __('ui.reservation_must_end_same_day')]);
        }

        $data['outlets_id'] = $outlet->outlets_id;
        $data['end_time'] = $end->format('H:i:s');
        $data['start_time'] = $start->format('H:i:s');
        $data['reservation_date'] = $start->toDateString();
        $data['guest_count'] = (int) $data['guest_count'];
        $data['step'] = 'tables';

        $this->releaseCurrentHold($request, $outlet, $request->session()->get($this->flowKey($outlet->code), []));
        $request->session()->put($this->flowKey($outlet->code), $data);

        return redirect()->route('reservasi.outlet', ['outlet' => $outlet->code]);
    }

    public function holdTable(Request $request, string $outlet): RedirectResponse
    {
        $outlet = $this->findOutlet($outlet);
        $flowKey = $this->flowKey($outlet->code);
        $today = Carbon::now(config('reservation.timezone'))->toDateString();
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+().\\-\\s]{8,20}$/'],
            'customer_email' => ['required', 'email', 'max:100'],
            'adult_count' => ['required', 'integer', 'min:1', 'max:50'],
            'children_count' => ['required', 'integer', 'min:0', 'max:50'],
            'reservation_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:' . $today],
            'start_time' => ['required', 'date_format:H:i'],
            'tables_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['guest_count'] = (int) $data['adult_count'] + (int) $data['children_count'];
        if ($data['guest_count'] > 50) {
            throw ValidationException::withMessages(['children_count' => __('ui.max_guest_count')]);
        }
        $timezone = config('reservation.timezone');
        $start = Carbon::createFromFormat('Y-m-d H:i', $data['reservation_date'] . ' ' . $data['start_time'], $timezone);
        if ($start->lessThanOrEqualTo(Carbon::now($timezone))) {
            throw ValidationException::withMessages(['start_time' => __('ui.time_must_be_future')]);
        }
        $end = $start->copy()->addMinutes((int) config('reservation.duration_minutes', 120));
        if (! $end->isSameDay($start)) {
            throw ValidationException::withMessages(['start_time' => __('ui.reservation_must_end_same_day')]);
        }
        $flow = [
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'],
            'adult_count' => (int) $data['adult_count'],
            'children_count' => (int) $data['children_count'],
            'guest_count' => $data['guest_count'],
            'reservation_date' => $start->toDateString(),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
            'notes' => $data['notes'] ?? null,
            'step' => 'details',
        ];
        $sessionHash = $this->sessionHash($request);
        $lock = Cache::lock('reservation-hold-session:' . $sessionHash, 10);

        try {
            $hold = $lock->block(5, function () use ($data, $flow, $outlet, $sessionHash): ReservationHold {
                return DB::transaction(function () use ($data, $flow, $outlet, $sessionHash): ReservationHold {
                    $active = ReservationHold::where('session_token_hash', $sessionHash)
                        ->where('status', 'active')
                        ->where('expires_at', '>', now())
                        ->lockForUpdate()
                        ->first();
                    if ($active) {
                        throw ValidationException::withMessages(['tables_id' => __('ui.active_hold_exists')]);
                    }
                    ReservationHold::where('session_token_hash', $sessionHash)
                        ->where('status', 'active')
                        ->where('expires_at', '<=', now())
                        ->update(['status' => 'expired']);

                    $table = Table::where('tables_id', (int) $data['tables_id'])
                        ->where('outlets_id', $outlet->outlets_id)
                        ->whereHas('area', fn ($query) => $query->where('outlets_id', $outlet->outlets_id)->where('is_active', true))
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->first();
                    if (! $table || (int) $table->capacity < (int) $flow['guest_count']) {
                        throw ValidationException::withMessages(['tables_id' => __('ui.table_unavailable')]);
                    }

                    $availableIds = $this->availability->availableTables(
                        $outlet->outlets_id,
                        $table->areas_id,
                        $flow['reservation_date'],
                        $flow['start_time'],
                        $flow['end_time'],
                        (int) $flow['guest_count'],
                    )->modelKeys();
                    if (! in_array((int) $table->tables_id, array_map('intval', $availableIds), true)) {
                        throw ValidationException::withMessages(['tables_id' => __('ui.table_unavailable')]);
                    }

                    $now = now();
                    $hold = ReservationHold::create([
                        'session_token_hash' => $sessionHash,
                        'outlets_id' => $outlet->outlets_id,
                        'reservation_date' => $flow['reservation_date'],
                        'start_time' => $flow['start_time'],
                        'end_time' => $flow['end_time'],
                        'started_at' => $now,
                        'expires_at' => $now->copy()->addMinutes((int) config('reservation.hold_minutes', 10)),
                        'status' => 'active',
                    ]);
                    $hold->tables()->attach($table->tables_id);

                    return $hold;
                });
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['tables_id' => __('ui.active_hold_exists')]);
        }

        $flow['step'] = 'menu';
        $flow['hold_id'] = $hold->reservation_holds_id;
        $flow['tables_id'] = (int) $data['tables_id'];
        $request->session()->put($flowKey, $flow);

        return redirect()->route('reservasi.outlet', $outlet->code);
    }

    public function saveMenu(Request $request, string $outlet): RedirectResponse
    {
        $outlet = $this->findOutlet($outlet);
        $flowKey = $this->flowKey($outlet->code);
        $flow = $request->session()->get($flowKey);
        if (! is_array($flow) || empty($flow['hold_id'])) {
            return redirect()->route('reservasi.outlet', $outlet->code)
                ->withErrors(['hold' => __('ui.hold_expired')]);
        }

        $validated = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*' => ['required', 'integer', 'min:0', 'max:50'],
        ]);
        $quantities = collect($validated['items'] ?? [])
            ->mapWithKeys(fn ($quantity, $id) => [(int) $id => (int) $quantity])
            ->filter(fn ($quantity) => $quantity > 0);
        $availableIds = $outlet->menuItems()
            ->where('is_available', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->whereIn('menu_items_id', $quantities->keys())
            ->pluck('menu_items_id')
            ->map(fn ($id) => (int) $id);

        if ($availableIds->count() !== $quantities->count()) {
            throw ValidationException::withMessages(['items' => __('ui.menu_item_unavailable')]);
        }

        $flow['menu_items'] = $quantities->all();
        $flow['step'] = 'review';
        $request->session()->put($flowKey, $flow);

        return redirect()->route('reservasi.outlet', $outlet->code);
    }

    public function confirm(Request $request, string $outlet): RedirectResponse
    {
        $outlet = $this->findOutlet($outlet);
        $flowKey = $this->flowKey($outlet->code);
        $flow = $request->session()->get($flowKey);
        if (! is_array($flow) || empty($flow['hold_id'])) {
            return redirect()->route('reservasi.outlet', $outlet->code)
                ->withErrors(['reservation' => __('ui.hold_expired')]);
        }

                $validated = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $proofFile = $validated['payment_proof'];

        // Catatan dari halaman review dipakai; bila kolomnya tidak terkirim, pakai catatan dari langkah pertama.
        $notes = array_key_exists('notes', $validated)
            ? trim((string) $validated['notes'])
            : trim((string) ($flow['notes'] ?? ''));
        $notes = $notes === '' ? null : $notes;
        $storedProofPath = null;

        $sessionHash = $this->sessionHash($request);
        $requestedItems = collect($flow['menu_items'] ?? [])->mapWithKeys(
            fn ($quantity, $id) => [(int) $id => (int) $quantity],
        )->filter(fn ($quantity) => $quantity > 0);
        $availableMenuIds = $outlet->menuItems()
            ->where('is_available', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->whereIn('menu_items_id', $requestedItems->keys())
            ->pluck('menu_items_id')
            ->map(fn ($id) => (int) $id);
        if ($availableMenuIds->count() !== $requestedItems->count()) {
            $flow['step'] = 'menu';
            $request->session()->put($flowKey, $flow);

            return redirect()->route('reservasi.outlet', $outlet->code)
                ->withErrors(['items' => __('ui.menu_item_unavailable')]);
        }

        try {
                        $reservation = DB::transaction(function () use ($flow, $outlet, $sessionHash, $requestedItems, $proofFile, $notes, &$storedProofPath): ?Reservation {
            $hold = ReservationHold::where('reservation_holds_id', $flow['hold_id'])
                ->where('outlets_id', $outlet->outlets_id)
                ->where('session_token_hash', $sessionHash)
                ->lockForUpdate()
                ->first();

            if (! $hold || $hold->status !== 'active' || $hold->expires_at->isPast()) {
                if ($hold && $hold->status === 'active') {
                    $hold->update(['status' => 'expired']);
                }
                return null;
            }

            $table = $hold->tables()->lockForUpdate()->first();
            if (! $table || (int) $table->capacity < (int) $flow['guest_count']) {
                $hold->update(['status' => 'released']);
                return null;
            }

            $menuItems = MenuItem::query()
                ->where('outlets_id', $outlet->outlets_id)
                ->where('is_available', true)
                ->whereHas('category', fn ($query) => $query->where('is_active', true))
                ->whereIn('menu_items_id', $requestedItems->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('menu_items_id');
            if ($menuItems->count() !== $requestedItems->count()) {
                $hold->update(['status' => 'released']);
                return null;
            }

            $subtotal = round($requestedItems
                ->map(fn ($quantity, $id) => (float) $menuItems[$id]->price * $quantity)
                ->sum(), 2);
            $taxServiceRate = $outlet->taxServiceRates()
                ->whereDate('effective_from', '<=', $flow['reservation_date'])
                ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $flow['reservation_date']))
                ->orderByDesc('effective_from')
                ->first();
            $serviceRate = (float) ($taxServiceRate?->service_rate ?? 5);
            $taxRate = (float) ($taxServiceRate?->tax_rate ?? 10);
            $serviceAmount = round($subtotal * $serviceRate / 100, 2);
            $taxAmount = round(($subtotal + $serviceAmount) * $taxRate / 100, 2);

            $reservation = Reservation::create([
                'reservation_code' => 'RRMS-' . Str::upper(Str::random(12)),
                'outlets_id' => $outlet->outlets_id,
                'customer_name' => $flow['customer_name'],
                'customer_phone' => $flow['customer_phone'],
                'customer_email' => $flow['customer_email'],
                'guest_count' => $flow['guest_count'],
                'reservation_date' => $flow['reservation_date'],
                'start_time' => $flow['start_time'],
                'end_time' => $flow['end_time'],
                'reservation_status' => 'pending_payment',
                'subtotal' => $subtotal,
                'service_rate_percent' => $serviceRate,
                'service_amount' => $serviceAmount,
                'tax_rate_percent' => $taxRate,
                'tax_amount' => $taxAmount,
                'grand_total' => $subtotal + $serviceAmount + $taxAmount,
                'notes' => $notes,
            ]);
            $reservationStart = Carbon::parse($reservation->reservation_date->toDateString() . ' ' . $reservation->start_time, config('reservation.timezone'));
            ReservationQrToken::create([
                'reservations_id' => $reservation->reservations_id,
                'token' => Str::random(64),
                'valid_from' => $reservationStart,
                'valid_until' => Carbon::parse($reservation->reservation_date->toDateString() . ' ' . $reservation->end_time, config('reservation.timezone')),
                'is_active' => false,
            ]);
            $storedProofPath = $proofFile->store("payment-proofs/{$reservation->reservations_id}", 'local');
            if (! $storedProofPath) {
                throw ValidationException::withMessages(['payment_proof' => 'Bukti pembayaran gagal disimpan. Silakan pilih file dan coba lagi.']);
            }
            Payment::create([
                'reservations_id' => $reservation->reservations_id,
                'payment_method' => 'bank_transfer',
                'bank_destination' => substr((string) config('reservation.bank.account_number', 'unconfigured'), 0, 50),
                'amount' => $reservation->grand_total,
                'payment_status' => 'pending_verification',
                'proof_file_path' => $storedProofPath,
                'proof_uploaded_at' => now(),
            ]);
            $reservation->tables()->attach($table->tables_id);
            foreach ($requestedItems as $menuItemId => $quantity) {
                $menuItem = $menuItems[$menuItemId];
                $unitPrice = (float) $menuItem->price;
                ReservationItem::create([
                    'reservations_id' => $reservation->reservations_id,
                    'menu_items_id' => $menuItem->menu_items_id,
                    'item_name_snapshot' => $menuItem->name,
                    'unit_price_snapshot' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => round($unitPrice * $quantity, 2),
                ]);
            }
            $hold->update(['status' => 'converted']);
            AuditLog::create([
                'action' => 'reservation_created',
                'entity_type' => 'reservation',
                'entity_id' => $reservation->reservations_id,
                'new_values' => [
                    'reservation_status' => $reservation->reservation_status,
                    'outlets_id' => $reservation->outlets_id,
                    'tables_id' => $table->tables_id,
                    'reservation_date' => $reservation->reservation_date->toDateString(),
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                    'guest_count' => $reservation->guest_count,
                    'subtotal' => $subtotal,
                    'service_amount' => $serviceAmount,
                    'tax_amount' => $taxAmount,
                    'grand_total' => $reservation->grand_total,
                ],
            ]);

                return $reservation;
            });
        } catch (\Throwable $exception) {
            if ($storedProofPath) {
                Storage::disk('local')->delete($storedProofPath);
            }

            throw $exception;
        }

        if (! $reservation) {
            $flow['step'] = 'tables';
            unset($flow['hold_id']);
            $request->session()->put($flowKey, $flow);

            return redirect()->route('reservasi.outlet', ['outlet' => $outlet->code, 'step' => 'tables'])
                ->withErrors(['hold' => __('ui.hold_expired')]);
        }

        $request->session()->put($this->confirmationKey($outlet->code), $reservation->reservation_code);
        $request->session()->put(GuestReservationController::SESSION_KEY, mb_strtolower($reservation->customer_email));
        $request->session()->forget($flowKey);

        return redirect()->route('reservasi.confirmation', [
            'outlet' => $outlet->code,
            'code' => $reservation->reservation_code,
        ])->with('payment_submitted', true);
    }

    public function confirmation(Request $request, string $outlet, string $code): View
    {
        $outlet = $this->findOutlet($outlet);
        abort_unless($this->canViewReservation($request, $outlet, $code), 404);

        $reservation = Reservation::with(['tables.area', 'items', 'latestPayment', 'qrToken'])
            ->where('outlets_id', $outlet->outlets_id)
            ->where('reservation_code', $code)
            ->firstOrFail();

        if (! $reservation->latestPayment) {
            $reservation->payments()->create([
                'payment_method' => 'bank_transfer',
                'bank_destination' => substr((string) config('reservation.bank.account_number', 'unconfigured'), 0, 50),
                'amount' => $reservation->grand_total,
                'payment_status' => 'unpaid',
            ]);
            $reservation->load('latestPayment');
        }
        if (! $reservation->qrToken) {
            $reservationStart = Carbon::parse($reservation->reservation_date->toDateString() . ' ' . $reservation->start_time, config('reservation.timezone'));
            $reservation->qrToken()->create([
                'token' => Str::random(64),
                'valid_from' => $reservationStart,
                'valid_until' => Carbon::parse($reservation->reservation_date->toDateString() . ' ' . $reservation->end_time, config('reservation.timezone')),
                'is_active' => $reservation->reservation_status === 'confirmed',
            ]);
            $reservation->load('qrToken');
        }

        $qrDataUri = $reservation->qrToken?->token
            ? app(ReservationQrCode::class)->dataUri($reservation->qrToken->token)
            : null;

        return view('reservasi.konfirmasi', compact('outlet', 'reservation', 'qrDataUri'));
    }

    public function downloadQr(Request $request, string $outlet, string $code)
    {
        $outlet = $this->findOutlet($outlet);
        abort_unless($request->session()->get($this->confirmationKey($outlet->code)) === $code, 404);

        $reservation = Reservation::with(['qrToken', 'latestPayment'])
            ->where('outlets_id', $outlet->outlets_id)
            ->where('reservation_code', $code)
            ->firstOrFail();
        abort_unless($reservation->qrToken?->token, 404);
        abort_unless(
            $reservation->reservation_status === 'confirmed'
                && $reservation->latestPayment?->payment_status === 'paid'
                && $reservation->qrToken->is_active,
            403,
            'QR reservasi tersedia setelah pembayaran dikonfirmasi finance.',
        );

        $svg = app(ReservationQrCode::class)->svg($reservation->qrToken->token);

        return response()->streamDownload(
            static function () use ($svg): void {
                echo $svg;
            },
            $reservation->reservation_code . '-qr.svg',
            ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'private, no-store'],
        );
    }

    public function uploadPaymentProof(Request $request, string $outlet, string $code): RedirectResponse
    {
        $outlet = $this->findOutlet($outlet);
        abort_unless($request->session()->get($this->confirmationKey($outlet->code)) === $code, 404);
        $validated = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        $reservation = Reservation::where('outlets_id', $outlet->outlets_id)
            ->where('reservation_code', $code)
            ->firstOrFail();
        abort_if($reservation->reservation_status === 'confirmed', 409, 'Reservasi ini sudah dikonfirmasi.');
        $payment = $reservation->payments()->latest('payments_id')->firstOrFail();
        abort_if($payment->payment_status === 'paid', 409, 'Pembayaran ini sudah diterima.');

        $file = $validated['payment_proof'];
        $path = $file->store("payment-proofs/{$reservation->reservations_id}", 'local');
        if ($payment->proof_file_path) {
            Storage::disk('local')->delete($payment->proof_file_path);
        }
        $payment->update([
            'proof_file_path' => $path,
            'proof_uploaded_at' => now(),
            'payment_status' => 'pending_verification',
            'verified_at' => null,
            'users_id_verified_by' => null,
            'rejection_reason' => null,
        ]);

        return redirect()->route('reservasi.confirmation', ['outlet' => $outlet->code, 'code' => $code])
            ->with('payment_submitted', true);
    }

        // Boleh membuka reservasi ini jika: (1) baru dibuat di sesi ini, atau
    // (2) pelanggan sudah terverifikasi lewat halaman Reservasi Saya dan reservasi itu miliknya.
    private function canViewReservation(Request $request, Outlet $outlet, string $code): bool
    {
        if ($request->session()->get($this->confirmationKey($outlet->code)) === $code) {
            return true;
        }

        $email = $request->session()->get(GuestReservationController::SESSION_KEY);
        if (! $email) {
            return false;
        }

        return Reservation::where('outlets_id', $outlet->outlets_id)
            ->where('reservation_code', $code)
            ->where('customer_email', $email)
            ->exists();
    }

    private function findOutlet(string $code): Outlet
    {
        $query = Outlet::where('code', $code);

        // Outlet nonaktif menutup reservasi baru, tetapi reservasi yang sudah ada tetap dapat dibuka.
        if (! request()->routeIs('reservasi.confirmation', 'reservasi.qr-download', 'reservasi.payment-proof')) {
            $query->where('is_active', true);
        }

        return $query->firstOrFail();
    }

    private function flowKey(string $outletCode): string
    {
        return 'reservation_flow.' . $outletCode;
    }

    private function confirmationKey(string $outletCode): string
    {
        return 'reservation_confirmation.' . $outletCode;
    }

    private function sessionHash(Request $request): string
    {
        return hash('sha256', (string) $request->session()->getId());
    }

    private function activeFlowHold(Request $request, Outlet $outlet, array $flow): ?ReservationHold
    {
        if (empty($flow['hold_id'])) {
            return null;
        }

        return ReservationHold::with('tables.area')
            ->where('reservation_holds_id', $flow['hold_id'])
            ->where('outlets_id', $outlet->outlets_id)
            ->where('session_token_hash', $this->sessionHash($request))
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();
    }

    private function releaseCurrentHold(Request $request, Outlet $outlet, array $flow): void
    {
        if (empty($flow['hold_id'])) {
            return;
        }

        ReservationHold::where('reservation_holds_id', $flow['hold_id'])
            ->where('outlets_id', $outlet->outlets_id)
            ->where('session_token_hash', $this->sessionHash($request))
            ->where('status', 'active')
            ->update(['status' => 'released']);
    }
}
