<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ReservationSettings
{
    public const CACHE_KEY = 'rrms.app_settings';

    /** @return array<string, string|null> */
    public function all(): array
    {
        if (! Schema::hasTable('app_settings')) {
            return [];
        }

        return Cache::remember(self::CACHE_KEY, 300, function (): array {
            return AppSetting::query()
                ->pluck('value', 'key')
                ->map(fn ($v) => $v === null ? null : (string) $v)
                ->all();
        });
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $all = $this->all();
        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }

        return $default;
    }

    /** @param array<string, scalar|null> $values */
    public function setMany(array $values, ?int $userId = null): void
    {
        foreach ($values as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value === null ? null : (string) $value,
                    'users_id_updated_by' => $userId,
                ],
            );
        }

        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function holdMinutes(): int
    {
        $val = (int) $this->get('hold_minutes', (string) config('reservation.hold_minutes', 10));

        return max(1, $val);
    }

    public function durationMinutes(): int
    {
        $val = (int) $this->get('duration_minutes', (string) config('reservation.duration_minutes', 120));

        return max(15, $val);
    }

    public function maxGuests(): int
    {
        $val = (int) $this->get('max_guests', '50');

        return max(1, $val);
    }

    public function bankName(): string
    {
        return (string) ($this->get('bank_name') ?: config('reservation.bank.name') ?: '');
    }

    public function bankAccountNumber(): string
    {
        return (string) ($this->get('bank_account_number') ?: config('reservation.bank.account_number') ?: '');
    }

    public function bankAccountHolder(): string
    {
        return (string) ($this->get('bank_account_holder') ?: config('reservation.bank.account_holder') ?: '');
    }

    /** @return array{name: string, account_number: string, account_holder: string} */
    public function bankInfo(): array
    {
        return [
            'name' => $this->bankName(),
            'account_number' => $this->bankAccountNumber(),
            'account_holder' => $this->bankAccountHolder(),
        ];
    }
}
