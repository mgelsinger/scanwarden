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
        Schema::create('summoned_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sector_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('rarity')->default('common');
            $table->integer('tier')->default(1);
            $table->integer('evolution_level')->default(0);
            $table->integer('hp');
            $table->integer('attack');
            $table->integer('defense');
            $table->integer('speed');
            $table->text('passive_ability')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'sector_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('summoned_units');
    }
};
