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
        Schema::table('battle_matches', function (Blueprint $table) {
            // Add fields for team-based battles
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('attacker_team_id')->after('user_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('defender_team_id')->after('attacker_team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('winner')->nullable()->after('defender_team_id'); // 'attacker' or 'defender'
            $table->integer('total_turns')->nullable()->after('winner');
            $table->integer('rating_change')->default(0)->after('total_turns');

            // Make old PvP fields nullable (for backward compatibility)
            $table->foreignId('attacker_id')->nullable()->change();
            $table->foreignId('defender_id')->nullable()->change();
            $table->integer('attacker_rating_before')->nullable()->change();
            $table->integer('attacker_rating_after')->nullable()->change();
            $table->integer('defender_rating_before')->nullable()->change();
            $table->integer('defender_rating_after')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('battle_matches', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['attacker_team_id']);
            $table->dropForeign(['defender_team_id']);
            $table->dropColumn(['user_id', 'attacker_team_id', 'defender_team_id', 'winner', 'total_turns', 'rating_change']);
        });
    }
};
