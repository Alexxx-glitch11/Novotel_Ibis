<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    protected $table = 'reservations';

    protected $primaryKey = 'reservations_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'reservation_code',
        'outlets_id',
        'outlet_contents_id',
        'users_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'guest_count',
        'reservation_date',
        'start_time',
        'end_time',
        'reservation_status',
        'subtotal',
        'service_rate_percent',
        'service_amount',
        'tax_rate_percent',
        'tax_amount',
        'grand_total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'guest_count' => 'integer',
            'reservation_date' => 'date',
            'subtotal' => 'decimal:2',
            'service_rate_percent' => 'decimal:2',
            'service_amount' => 'decimal:2',
            'tax_rate_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlets_id', 'outlets_id');
    }

    public function eventContent(): BelongsTo
    {
        return $this->belongsTo(OutletContent::class, 'outlet_contents_id', 'outlet_contents_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id', 'users_id');
    }

    public function tables(): BelongsToMany
    {
        return $this->belongsToMany(Table::class, 'reservation_tables', 'reservations_id', 'tables_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class, 'reservations_id', 'reservations_id');
    }

    public function qrToken(): HasOne
    {
        return $this->hasOne(ReservationQrToken::class, 'reservations_id', 'reservations_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'reservations_id', 'reservations_id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class, 'reservations_id', 'reservations_id')->latestOfMany('payments_id');
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class, 'reservations_id', 'reservations_id');
    }

    public function cancellationRequests(): HasMany
    {
        return $this->hasMany(CancellationRequest::class, 'reservations_id', 'reservations_id');
    }

    /**
     * Hasilkan kode reservasi unik dengan prefix outlet dan tanggal kunjungan,
     * diikuti akhiran acak 6 karakter kriptografis (Crockford-32 tanpa 0/O/1/I).
     * Contoh: RRMS-NOVFE-2011-7K9M4X, RRMS-IBISK-2011-3P8W2Q.
     */
    public static function generateCode(Outlet $outlet, string $reservationDate): string
    {
        $codeLower = strtolower((string) $outlet->code);
        $prefix = match ($codeLower) {
            'novotel' => 'NOVFE',
            'ibis' => 'IBISK',
            default => substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($outlet->code)) ?: 'OUTLT', 0, 5),
        };
        $datePart = Carbon::parse($reservationDate)->format('dm');
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $maxIndex = strlen($alphabet) - 1;

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, $maxIndex)];
            }
            $candidate = sprintf('RRMS-%s-%s-%s', $prefix, $datePart, $suffix);
        } while (self::where('reservation_code', $candidate)->exists());

        return $candidate;
    }
}
