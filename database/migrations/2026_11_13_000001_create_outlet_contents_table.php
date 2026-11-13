<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_contents', function (Blueprint $table) {
            $table->id('outlet_contents_id');
            $table->foreignId('outlets_id')->constrained('outlets', 'outlets_id')->cascadeOnDelete();
            $table->foreignId('users_id_created_by')->nullable()->constrained('users', 'users_id')->nullOnDelete();
            $table->string('content_type', 20)->default('promotion');
            $table->string('title', 120);
            $table->text('description');
            $table->string('image_path')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->index(['outlets_id', 'status', 'starts_on', 'ends_on'], 'idx_outlet_content_window');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_contents');
    }
};
