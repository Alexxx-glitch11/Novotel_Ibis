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
        Schema::create('reservation_tables', function (Blueprint $table) {
            $table->id('reservation_tables_id'); // Primary Key: reservation_tables_id
            $table->foreignId('reservations_id')->constrained('reservations', 'reservations_id')->cascadeOnDelete();
            $table->foreignId('tables_id')->constrained('tables', 'tables_id')->cascadeOnDelete();

            $table->unique(['reservations_id', 'tables_id'], 'uq_rsv_table');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_tables');
    }
};
