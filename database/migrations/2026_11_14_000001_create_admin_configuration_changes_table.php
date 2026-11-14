<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_configuration_changes', function (Blueprint $table): void {
            $table->id();
            $table->string('config_key', 60);
            $table->json('current_value')->nullable();
            $table->json('proposed_value');
            $table->string('status', 20)->default('pending');
            $table->foreignId('users_id_requested_by')->nullable()->constrained('users', 'users_id')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['config_key', 'status'], 'idx_admin_config_pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_configuration_changes');
    }
};
