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
        Schema::table('summoned_units', function (Blueprint $table) {
            $table->string('source')->default('summon')->after('passive_ability');
            // source can be: 'starter', 'summon', 'reward', 'transmute', etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('summoned_units', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
