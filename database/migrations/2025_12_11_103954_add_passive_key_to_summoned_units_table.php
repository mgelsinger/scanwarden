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
            $table->string('passive_key')->nullable()->after('passive_ability');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('summoned_units', function (Blueprint $table) {
            $table->dropColumn('passive_key');
        });
    }
};
