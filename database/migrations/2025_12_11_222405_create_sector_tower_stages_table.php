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
        Schema::create('sector_tower_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tower_id')->constrained('sector_towers')->cascadeOnDelete();
            $table->unsignedInteger('floor'); // 1, 2, 3...
            $table->json('enemy_team')->comment('Definition of enemy units');
            $table->unsignedInteger('recommended_power')->default(0);
            $table->json('rewards')->nullable(); // JSON payload, same style as quests/transmuter
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tower_id', 'floor']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sector_tower_stages');
    }
};
