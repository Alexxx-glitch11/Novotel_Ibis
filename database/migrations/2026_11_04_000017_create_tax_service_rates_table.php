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
        Schema::create('tax_service_rates', function (Blueprint $table) {
            $table->id('tax_service_rates_id'); // Primary Key: tax_service_rates_id
            $table->foreignId('outlets_id')->constrained('outlets', 'outlets_id')->cascadeOnDelete();
            $table->decimal('tax_rate', 5, 2);
            $table->decimal('service_rate', 5, 2);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->foreignId('users_id_updated_by')->nullable()->constrained('users', 'users_id')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_service_rates');
    }
};
