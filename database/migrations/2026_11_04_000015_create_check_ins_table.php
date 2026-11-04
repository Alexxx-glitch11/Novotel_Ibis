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
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id('check_ins_id'); // Primary Key: check_ins_id
            $table->foreignId('reservations_id')->constrained('reservations', 'reservations_id')->cascadeOnDelete();
            $table->foreignId('users_id_checked_in_by')->constrained('users', 'users_id')->cascadeOnDelete();
            $table->unsignedInteger('guest_count_arrived');
            $table->unsignedInteger('cumulative_guest_count');
            $table->timestamp('checked_in_at')->useCurrent();
            $table->string('notes', 255)->nullable();

            $table->index('reservations_id', 'idx_checkin_rsv');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
