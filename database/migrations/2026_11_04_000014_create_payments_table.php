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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payments_id'); // Primary Key: payments_id
            $table->foreignId('reservations_id')->constrained('reservations', 'reservations_id')->cascadeOnDelete();
            $table->string('payment_method', 50)->default('bank_transfer');
            $table->string('bank_destination', 50);
            $table->decimal('amount', 12, 2);
            $table->enum('payment_status', ['unpaid', 'pending_verification', 'paid', 'rejected'])->default('pending_verification');
            $table->string('proof_file_path', 255)->nullable();
            $table->timestamp('proof_uploaded_at')->nullable();
            $table->foreignId('users_id_verified_by')->nullable()->constrained('users', 'users_id')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['payment_status', 'created_at'], 'idx_payment_audit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
