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
        Schema::create('reservation_items', function (Blueprint $table) {
            $table->id('reservation_items_id'); // Primary Key: reservation_items_id
            $table->foreignId('reservations_id')->constrained('reservations', 'reservations_id')->cascadeOnDelete();
            $table->foreignId('menu_items_id')->constrained('menu_items', 'menu_items_id')->cascadeOnDelete();
            $table->string('item_name_snapshot', 100);
            $table->decimal('unit_price_snapshot', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_items');
    }
};
