<?php

return [
    'duration_minutes' => (int) env('RESERVATION_DURATION_MINUTES', 120),
    'hold_minutes' => (int) env('RESERVATION_HOLD_MINUTES', 10),
    'payment_proof_hold_minutes' => (int) env('RESERVATION_PAYMENT_HOLD_MINUTES', 30),
    'timezone' => env('RESERVATION_TIMEZONE', 'Asia/Jakarta'),
    'bank' => [
        'name' => env('RESERVATION_BANK_NAME'),
        'account_number' => env('RESERVATION_BANK_ACCOUNT_NUMBER'),
        'account_holder' => env('RESERVATION_BANK_ACCOUNT_HOLDER'),
    ],
];
