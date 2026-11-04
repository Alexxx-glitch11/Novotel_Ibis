<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservation_qr_tokens', function (Blueprint $table) {
            $table->id('reservation_qr_tokens_id'); // Primary Key: reservation_qr_tokens_id
            $table->foreignId('reservations_id')->unique()->constrained('reservations', 'reservations_id')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['token', 'is_active'], 'idx_qr_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_qr_tokens');
    }
};
