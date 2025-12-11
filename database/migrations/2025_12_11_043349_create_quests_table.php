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
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('type'); // 'daily', 'achievement'
            $table->string('category'); // 'scan', 'battle', 'evolution', etc.
            $table->integer('target_value');
            $table->boolean('is_daily')->default(false);
            $table->boolean('is_repeatable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('reward_payload');
            $table->string('reset_period')->nullable(); // 'daily', 'none'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quests');
    }
};
