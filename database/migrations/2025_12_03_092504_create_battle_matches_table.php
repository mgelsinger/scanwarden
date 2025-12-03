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
        Schema::create('battle_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attacker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('defender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('attacker_rating_before');
            $table->integer('attacker_rating_after');
            $table->integer('defender_rating_before');
            $table->integer('defender_rating_after');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['attacker_id', 'created_at']);
            $table->index(['defender_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battle_matches');
    }
};
