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
        Schema::create('evolution_rules', function (Blueprint $table) {
            $table->id();
            $table->integer('from_tier');
            $table->integer('to_tier');
            $table->integer('required_sector_energy');
            $table->decimal('hp_multiplier', 4, 2)->default(1.0);
            $table->decimal('attack_multiplier', 4, 2)->default(1.0);
            $table->decimal('defense_multiplier', 4, 2)->default(1.0);
            $table->decimal('speed_multiplier', 4, 2)->default(1.0);
            $table->string('new_name_suffix')->nullable();
            $table->text('new_trait')->nullable();
            $table->timestamps();

            $table->unique(['from_tier', 'to_tier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evolution_rules');
    }
};
