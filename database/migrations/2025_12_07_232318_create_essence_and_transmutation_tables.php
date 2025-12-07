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
        Schema::create('user_essence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('sector_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('amount')->default(0);
            $table->string('type')->default('generic'); // 'generic', 'sector'
            $table->timestamps();

            $table->unique(['user_id', 'sector_id', 'type']);
        });

        Schema::create('transmutation_recipes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->json('required_inputs'); // [{type: 'essence', amount: 100, sector_id: 1}, ...]
            $table->json('outputs'); // [{type: 'unit_summon', rarity: 'rare'}, ...]
            $table->foreignId('sector_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->integer('level_requirement')->default(1);
            $table->timestamps();
        });

        Schema::create('transmutation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained('transmutation_recipes')->onDelete('cascade');
            $table->json('inputs_consumed');
            $table->json('outputs_received');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transmutation_history');
        Schema::dropIfExists('transmutation_recipes');
        Schema::dropIfExists('user_essence');
    }
};
