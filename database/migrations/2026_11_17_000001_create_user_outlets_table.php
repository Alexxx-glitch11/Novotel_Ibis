<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_outlets', function (Blueprint $table): void {
            $table->foreignId('users_id')->constrained('users', 'users_id')->cascadeOnDelete();
            $table->foreignId('outlets_id')->constrained('outlets', 'outlets_id')->cascadeOnDelete();
            $table->primary(['users_id', 'outlets_id']);
        });

        DB::table('users')->whereNotNull('outlets_id')->orderBy('users_id')->get(['users_id', 'outlets_id'])
            ->each(fn (object $assignment) => DB::table('user_outlets')->insert([
                'users_id' => $assignment->users_id,
                'outlets_id' => $assignment->outlets_id,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('user_outlets');
    }
};
