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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id('reservations_id'); // Primary Key: reservations_id
            $table->string('reservation_code', 32)->unique();
            $table->foreignId('outlets_id')->constrained('outlets', 'outlets_id')->cascadeOnDelete();
            $table->foreignId('users_id')->nullable()->constrained('users', 'users_id')->nullOnDelete();
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20);
            $table->string('customer_email', 100);
            $table->unsignedInteger('guest_count'); // Maksimal 50 orang sesuai PRD
            $table->date('reservation_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('reservation_status', [
                'draft',
                'pending_payment',
                'confirmed',
                'partially_arrived',
                'checked_in',
                'completed',
                'cancelled',
                'expired',
                'no_show',
            ])->default('pending_payment');

            // Snapshot Finansial Terkunci Permanen
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('service_rate_percent', 5, 2)->default(5.00);
            $table->decimal('service_amount', 12, 2)->default(0.00);
            $table->decimal('tax_rate_percent', 5, 2)->default(10.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2)->default(0.00);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['outlets_id', 'reservation_date', 'start_time', 'end_time', 'reservation_status'], 'idx_rsv_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
