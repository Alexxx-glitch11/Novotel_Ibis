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
        Schema::create('reservation_hold_items', function (Blueprint $table) {
            $table->id('reservation_hold_items_id'); // Primary Key: reservation_hold_items_id
            $table->foreignId('reservation_holds_id')->constrained('reservation_holds', 'reservation_holds_id')->cascadeOnDelete();
            $table->foreignId('tables_id')->constrained('tables', 'tables_id')->cascadeOnDelete();

            $table->unique(['reservation_holds_id', 'tables_id'], 'uq_hold_table');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_hold_items');
    }
};
