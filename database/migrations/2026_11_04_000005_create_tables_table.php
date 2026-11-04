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
        Schema::create('tables', function (Blueprint $table) {
            $table->id('tables_id'); // Primary Key: tables_id
            $table->foreignId('outlets_id')->constrained('outlets', 'outlets_id')->cascadeOnDelete();
            $table->foreignId('areas_id')->constrained('areas', 'areas_id')->cascadeOnDelete();
            $table->string('table_code', 20);
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('min_capacity')->default(1);
            $table->decimal('position_x', 8, 2)->default(0.00);
            $table->decimal('position_y', 8, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['outlets_id', 'table_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
