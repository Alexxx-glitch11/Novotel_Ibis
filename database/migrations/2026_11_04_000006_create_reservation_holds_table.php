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
        Schema::create('reservation_holds', function (Blueprint $table) {
            $table->id('reservation_holds_id'); // Primary Key: reservation_holds_id
            $table->string('session_token_hash', 64);
            $table->foreignId('outlets_id')->constrained('outlets', 'outlets_id')->cascadeOnDelete();
            $table->date('reservation_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'converted', 'expired', 'released'])->default('active');
            $table->timestamps();

            $table->index(['status', 'expires_at', 'reservation_date'], 'idx_hold_lookup');
            $table->index('session_token_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_holds');
    }
};
