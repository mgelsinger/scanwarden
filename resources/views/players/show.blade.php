<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Player Profile
            </h2>
            @if ($isOwnProfile)
                <a href="{{ route('teams.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Manage Teams
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Player Header -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-3xl font-bold text-gray-900 mb-2">
                                {{ $user->name }}
                                @if ($isOwnProfile)
                                    <span class="text-lg text-blue-600">(You)</span>
                                @endif
                            </h3>
                            <div class="flex items-center gap-4">
                                <div class="text-2xl font-bold" style="color: {{ $tierColor }}">
                                    {{ $user->rating ?? 0 }} Rating
                                </div>
                                <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold"
                                      style="background-color: {{ $tierColor }}20; color: {{ $tierColor }};">
                                    {{ $tier }} Tier
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <a href="{{ route('leaderboard.index') }}"
                               class="text-sm text-blue-600 hover:underline">
                                View Leaderboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Battle Statistics -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-2xl font-bold mb-6">Battle Statistics</h3>

                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-gray-900">{{ $stats['total_battles'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Total Battles</div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-green-600">{{ $stats['wins'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Wins</div>
                        </div>
                        <div class="bg-red-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-red-600">{{ $stats['losses'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Losses</div>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-yellow-600">{{ $stats['draws'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Draws</div>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-blue-600">{{ $stats['win_rate'] }}%</div>
                            <div class="text-sm text-gray-600 mt-1">Win Rate</div>
                        </div>
                    </div>

                    @if ($stats['total_battles'] > 0)
                        <div class="mt-4 bg-gray-100 rounded-lg p-4">
                            <div class="text-sm text-gray-700">
                                <strong>{{ $user->name }}</strong> has {{ $stats['win_rate'] }}% win rate over {{ $stats['total_battles'] }} {{ Str::plural('battle', $stats['total_battles']) }}.
                            </div>
                        </div>
                    @else
                        <div class="mt-4 bg-gray-100 rounded-lg p-4">
                            <div class="text-sm text-gray-700 italic">
                                No battle history yet.
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sector Distribution -->
            @if ($sectorDistribution->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-2xl font-bold mb-6">Unit Collection</h3>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach ($sectorDistribution as $distribution)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="text-2xl font-bold text-gray-900">{{ $distribution->count }}</div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        {{ $distribution->sector ? $distribution->sector->name : 'Unknown' }} Units
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Teams -->
            @if ($teams->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-2xl font-bold mb-6">Teams</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach ($teams as $team)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h4 class="text-xl font-bold text-gray-900">{{ $team->name }}</h4>
                                            <p class="text-sm text-gray-500">{{ $team->units_count }} {{ Str::plural('unit', $team->units_count) }}</p>
                                        </div>
                                        @if ($isOwnProfile)
                                            <a href="{{ route('teams.show', $team) }}"
                                               class="text-sm text-blue-600 hover:underline">
                                                View
                                            </a>
                                        @endif
                                    </div>

                                    @if ($team->units->isNotEmpty())
                                        <div class="space-y-2">
                                            @foreach ($team->units as $unit)
                                                <div class="flex items-center justify-between text-sm bg-gray-50 rounded p-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-semibold text-gray-900">{{ $unit->name }}</span>
                                                        <span class="text-xs px-2 py-1 rounded"
                                                              style="background-color: {{ match($unit->rarity) {
                                                                  'Common' => '#95A5A6',
                                                                  'Uncommon' => '#27AE60',
                                                                  'Rare' => '#3498DB',
                                                                  'Epic' => '#9B59B6',
                                                                  'Legendary' => '#F39C12',
                                                                  default => '#95A5A6'
                                                              } }}20; color: {{ match($unit->rarity) {
                                                                  'Common' => '#95A5A6',
                                                                  'Uncommon' => '#27AE60',
                                                                  'Rare' => '#3498DB',
                                                                  'Epic' => '#9B59B6',
                                                                  'Legendary' => '#F39C12',
                                                                  default => '#95A5A6'
                                                              } }};">
                                                            {{ $unit->rarity }}
                                                        </span>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ $unit->sector ? $unit->sector->name : 'Unknown' }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Recent Battles -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-2xl font-bold mb-6">Recent Battles</h3>

                    @if ($recentBattles->isEmpty())
                        <div class="text-center text-gray-500 py-12">
                            <div class="text-6xl mb-4">⚔️</div>
                            <p>No battles yet.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($recentBattles as $battle)
                                @php
                                    $isAttacker = $battle->attacker_id === $user->id;
                                    $opponent = $isAttacker ? $battle->defender : $battle->attacker;
                                    $resultLabel = 'Draw';
                                    $resultColor = 'text-yellow-600';

                                    if ($battle->winner_id) {
                                        if ($battle->winner_id === $user->id) {
                                            $resultLabel = 'Win';
                                            $resultColor = 'text-green-600';
                                        } else {
                                            $resultLabel = 'Loss';
                                            $resultColor = 'text-red-600';
                                        }
                                    }

                                    $playerTeam = $isAttacker ? $battle->attackerTeam : $battle->defenderTeam;
                                    $opponentTeam = $isAttacker ? $battle->defenderTeam : $battle->attackerTeam;
                                @endphp

                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-4 mb-2">
                                                <!-- Player Side -->
                                                <div class="text-center">
                                                    <div class="text-sm text-gray-500">{{ $user->name }}</div>
                                                    <div class="text-base font-semibold text-gray-700">
                                                        {{ $playerTeam ? $playerTeam->name : 'Unknown Team' }}
                                                    </div>
                                                </div>

                                                <div class="text-2xl text-gray-400">⚔️</div>

                                                <!-- Opponent Side -->
                                                <div class="text-center">
                                                    <div class="text-sm text-gray-500">
                                                        @if ($opponent)
                                                            <a href="{{ route('players.show', $opponent) }}" class="text-blue-600 hover:underline">
                                                                {{ $opponent->name }}
                                                            </a>
                                                        @else
                                                            <span class="text-gray-400">AI / Practice</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-base font-semibold text-gray-700">
                                                        {{ $opponentTeam ? $opponentTeam->name : 'Training Dummies' }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                                <span>
                                                    Result: <span class="font-bold {{ $resultColor }}">{{ $resultLabel }}</span>
                                                </span>
                                                <span>
                                                    Role: <span class="font-semibold">{{ $isAttacker ? 'Attacker' : 'Defender' }}</span>
                                                </span>
                                                <span>
                                                    Turns: <span class="font-semibold">{{ $battle->total_turns ?? 0 }}</span>
                                                </span>
                                                <span class="ml-auto text-gray-500">
                                                    {{ $battle->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
