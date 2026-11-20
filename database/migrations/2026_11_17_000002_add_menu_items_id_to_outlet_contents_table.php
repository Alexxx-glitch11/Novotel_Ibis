<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlet_contents', function (Blueprint $table) {
            $table->foreignId('menu_items_id')
                ->nullable()
                ->after('outlets_id')
                ->constrained('menu_items', 'menu_items_id')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('outlet_contents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('menu_items_id');
        });
    }
};
