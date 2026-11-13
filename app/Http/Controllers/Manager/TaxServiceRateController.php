<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Outlet;
use App\Models\TaxServiceRate;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaxServiceRateController extends Controller
{
    public function index(Request $request): View
    {
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get();
        $outletFilter = $request->query('outlet', 'all');

        $displayOutlets = $outlets;
        if ($outletFilter !== 'all' && is_numeric($outletFilter)) {
            $displayOutlets = $outlets->where('outlets_id', (int) $outletFilter);
        }

        // Ambil rate aktif / terbaru per outlet
        $outletRates = [];
        foreach ($outlets as $outlet) {
            $currentRate = TaxServiceRate::where('outlets_id', $outlet->outlets_id)
                ->orderByDesc('effective_from')
                ->first();

            $outletRates[$outlet->outlets_id] = $currentRate;
        }

        // Ambil riwayat audit log perubahan tarif
        $historyQuery = AuditLog::with('user')
            ->whereIn('action', ['tax_updated', 'service_updated', 'rates_updated'])
            ->latest('created_at');

        $history = $historyQuery->take(15)->get();

        return view('manager.tax-service.index', compact(
            'outlets',
            'displayOutlets',
            'outletRates',
            'history',
            'outletFilter'
        ));
    }

    public function update(Request $request, Outlet $outlet): RedirectResponse
    {
        $validated = $request->validate([
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
        ], [
            'tax_rate.required' => 'Tarif pajak wajib diisi.',
            'tax_rate.numeric' => 'Tarif pajak harus berupa angka.',
            'service_rate.required' => 'Biaya layanan wajib diisi.',
            'service_rate.numeric' => 'Biaya layanan harus berupa angka.',
            'effective_from.required' => 'Tanggal berlaku mulai wajib dipilih.',
            'effective_from.date' => 'Format tanggal tidak valid.',
        ]);

        DB::transaction(function () use ($request, $outlet, $validated): void {
            $effectiveDate = Carbon::parse($validated['effective_from'])->toDateString();

            // Ambil tarif aktif sebelumnya
            $oldRate = TaxServiceRate::where('outlets_id', $outlet->outlets_id)
                ->orderByDesc('effective_from')
                ->first();

            $oldTax = $oldRate ? (float) $oldRate->tax_rate : null;
            $oldService = $oldRate ? (float) $oldRate->service_rate : null;

            $newTax = (float) $validated['tax_rate'];
            $newService = (float) $validated['service_rate'];

            // Jika ada rate lama, perbarui batas akhir berlaku (effective_until)
            if ($oldRate && $effectiveDate > $oldRate->effective_from->toDateString()) {
                $oldRate->update([
                    'effective_until' => Carbon::parse($effectiveDate)->subDay()->toDateString(),
                ]);
            }

            // Simpan tarif baru
            $newRateRecord = TaxServiceRate::create([
                'outlets_id' => $outlet->outlets_id,
                'tax_rate' => $newTax,
                'service_rate' => $newService,
                'effective_from' => $effectiveDate,
                'effective_until' => null,
                'users_id_updated_by' => $request->user()?->users_id,
            ]);

            $userName = $request->user()?->email ?? ($request->user()?->name ?? 'manager@' . strtolower($outlet->code ?? 'hotel'));

            // Catat ke Audit Log jika tarif pajak berubah
            if ($oldTax === null || $oldTax !== $newTax) {
                AuditLog::create([
                    'users_id' => $request->user()?->users_id,
                    'action' => 'tax_updated',
                    'entity_type' => 'tax_service_rate',
                    'entity_id' => $newRateRecord->tax_service_rates_id,
                    'old_values' => [
                        'outlet_name' => $outlet->name,
                        'field' => 'Pajak',
                        'tax_rate' => $oldTax !== null ? $oldTax . '%' : '—',
                    ],
                    'new_values' => [
                        'outlet_name' => $outlet->name,
                        'field' => 'Pajak',
                        'tax_rate' => $newTax . '%',
                        'operator' => $userName,
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            // Catat ke Audit Log jika biaya layanan berubah
            if ($oldService === null || $oldService !== $newService) {
                AuditLog::create([
                    'users_id' => $request->user()?->users_id,
                    'action' => 'service_updated',
                    'entity_type' => 'tax_service_rate',
                    'entity_id' => $newRateRecord->tax_service_rates_id,
                    'old_values' => [
                        'outlet_name' => $outlet->name,
                        'field' => 'Biaya layanan',
                        'service_rate' => $oldService !== null ? $oldService . '%' : '—',
                    ],
                    'new_values' => [
                        'outlet_name' => $outlet->name,
                        'field' => 'Biaya layanan',
                        'service_rate' => $newService . '%',
                        'operator' => $userName,
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        });

        return back()->with('success', "Tarif pajak dan biaya layanan untuk {$outlet->name} berhasil diperbarui.");
    }
}
