<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlet_contents', function (Blueprint $table) {
            $table->boolean('requires_table')->default(true)->after('status');
            $table->boolean('requires_menu')->default(true)->after('requires_table');
            $table->decimal('price_per_pax', 12, 2)->default(0)->after('requires_menu');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('outlet_contents_id')
                ->nullable()
                ->after('outlets_id')
                ->constrained('outlet_contents', 'outlet_contents_id')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outlet_contents_id');
        });

        Schema::table('outlet_contents', function (Blueprint $table) {
            $table->dropColumn(['requires_table', 'requires_menu', 'price_per_pax']);
        });
    }
};
