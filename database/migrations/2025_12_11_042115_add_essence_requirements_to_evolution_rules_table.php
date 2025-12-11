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
        Schema::table('evolution_rules', function (Blueprint $table) {
            $table->integer('required_generic_essence')->default(0)->after('required_sector_energy');
            $table->integer('required_sector_essence')->default(0)->after('required_generic_essence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evolution_rules', function (Blueprint $table) {
            $table->dropColumn(['required_generic_essence', 'required_sector_essence']);
        });
    }
};
