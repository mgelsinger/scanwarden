<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-6 mb-6 text-white">
                <h1 class="text-3xl font-bold mb-2">Welcome back, {{ auth()->user()->name }}!</h1>
                <p class="text-blue-100">Continue your journey as a Scanner and discover the secrets of the UPC universe.</p>
            </div>

            <!-- Onboarding / Next Steps -->
            <x-card title="Next Steps" class="mb-6">
                @php $s = $playerStatus; @endphp

                @if($s['next_action'] === 'scan_item')
                    <p class="text-sm text-gray-200">
                        Start by scanning your first item. Every scan earns sector energy and can summon units.
                    </p>
                    <a href="{{ route('scan.create') }}" class="inline-flex mt-3 px-3 py-1.5 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-500 text-white">
                        Go to Scan
                    </a>
                @elseif($s['next_action'] === 'build_team')
                    <p class="text-sm text-gray-200">
                        You've summoned units—now assemble your first team to enter battles and towers.
                    </p>
                    <a href="{{ route('teams.index') }}" class="inline-flex mt-3 px-3 py-1.5 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-500 text-white">
                        Build a Team
                    </a>
                @elseif($s['next_action'] === 'enter_tower')
                    <p class="text-sm text-gray-200">
                        Test your team in a Sector Tower for guaranteed rewards and progression.
                    </p>
                    <a href="{{ route('towers.index') }}" class="inline-flex mt-3 px-3 py-1.5 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-500 text-white">
                        Enter Towers
                    </a>
                @elseif($s['next_action'] === 'pvp_battle')
                    <p class="text-sm text-gray-200">
                        You're ready for PvP. Battle other players to climb the leaderboard.
                    </p>
                    <a href="{{ route('battles.index') }}" class="inline-flex mt-3 px-3 py-1.5 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-500 text-white">
                        Find a Battle
                    </a>
                @else
                    <p class="text-sm text-gray-200">
                        Keep scanning, evolving units, and climbing Towers to strengthen your roster.
                    </p>
                @endif
            </x-card>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Scans -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Scans</p>
                            <p class="text-3xl font-bold text-gray-800">{{ $stats['total_scans'] }}</p>
                        </div>
                        <div class="text-4xl">📱</div>
                    </div>
                </div>

                <!-- Total Units -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Units Collected</p>
                            <p class="text-3xl font-bold text-gray-800">{{ $stats['total_units'] }}</p>
                        </div>
                        <div class="text-4xl">⚔️</div>
                    </div>
                </div>

                <!-- Total Battles -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Battles Completed</p>
                            <p class="text-3xl font-bold text-gray-800">{{ $stats['total_battles'] }}</p>
                        </div>
                        <div class="text-4xl">🏆</div>
                    </div>
                </div>

                <!-- Rating -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Rating</p>
                            <p class="text-3xl font-bold" style="color: {{ $stats['rating_color'] }}">{{ $stats['rating'] }}</p>
                            <p class="text-xs font-semibold px-2 py-1 rounded inline-block mt-1"
                               style="background-color: {{ $stats['rating_color'] }}20; color: {{ $stats['rating_color'] }};">
                                {{ $stats['rating_tier'] }}
                            </p>
                        </div>
                        <div class="text-4xl">📊</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <a href="{{ route('scan.create') }}"
                           class="flex flex-col items-center justify-center p-4 bg-blue-50 border-2 border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                            <div class="text-3xl mb-2">📱</div>
                            <div class="font-semibold text-blue-800">Scan UPC</div>
                        </a>
                        <a href="{{ route('units.index') }}"
                           class="flex flex-col items-center justify-center p-4 bg-green-50 border-2 border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                            <div class="text-3xl mb-2">👥</div>
                            <div class="font-semibold text-green-800">View Units</div>
                        </a>
                        <a href="{{ route('teams.index') }}"
                           class="flex flex-col items-center justify-center p-4 bg-purple-50 border-2 border-purple-200 rounded-lg hover:bg-purple-100 transition-colors">
                            <div class="text-3xl mb-2">🛡️</div>
                            <div class="font-semibold text-purple-800">Manage Teams</div>
                        </a>
                        <a href="{{ route('battles.create') }}"
                           class="flex flex-col items-center justify-center p-4 bg-red-50 border-2 border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                            <div class="text-3xl mb-2">⚔️</div>
                            <div class="font-semibold text-red-800">Start Battle</div>
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Sector Energy -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-4">Sector Energy</h3>
                        @if ($sectorEnergies->isEmpty())
                            <p class="text-gray-500 text-center py-4">Start scanning to collect sector energy!</p>
                        @else
                            <div class="space-y-3">
                                @foreach ($sectorEnergies as $energy)
                                    <div class="flex items-center justify-between p-3 rounded"
                                         style="background-color: {{ $energy->sector->color }}10;">
                                        <div class="flex items-center gap-3">
                                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $energy->sector->color }};"></div>
                                            <span class="font-semibold">{{ $energy->sector->name }}</span>
                                        </div>
                                        <span class="text-xl font-bold" style="color: {{ $energy->sector->color }};">
                                            {{ $energy->current_energy }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-4">Recent Units</h3>
                        @if ($recentUnits->isEmpty())
                            <p class="text-gray-500 text-center py-4">No units summoned yet. Start scanning!</p>
                        @else
                            <div class="space-y-2">
                                @foreach ($recentUnits as $unit)
                                    <a href="{{ route('units.show', $unit) }}"
                                       class="flex items-center justify-between p-3 rounded hover:bg-gray-50 border border-gray-200">
                                        <div>
                                            <div class="font-semibold">{{ $unit->name }}</div>
                                            <div class="text-xs text-gray-500">Tier {{ $unit->tier }}</div>
                                        </div>
                                        <span class="text-xs px-2 py-1 rounded font-semibold"
                                              style="background-color: {{ $unit->sector->color }}20; color: {{ $unit->sector->color }};">
                                            {{ $unit->sector->name }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Recent Battles -->
            @if ($recentBattles->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-4">Recent Battles</h3>
                        <div class="space-y-3">
                            @foreach ($recentBattles as $battle)
                                <a href="{{ route('battles.show', $battle) }}"
                                   class="flex items-center justify-between p-4 rounded hover:bg-gray-50 border border-gray-200">
                                    <div class="flex items-center gap-4">
                                        <div class="text-2xl">⚔️</div>
                                        <div>
                                            <div class="font-semibold">
                                                {{ $battle->attackerTeam->name }} vs {{ $battle->defenderTeam->name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                Winner: <span class="font-semibold text-green-600">
                                                    {{ $battle->winner === 'attacker' ? $battle->attackerTeam->name : $battle->defenderTeam->name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $battle->created_at->diffForHumans() }}
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Lore Progress -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold">Lore Progress</h3>
                        <a href="{{ route('lore.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                            View All →
                        </a>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-4xl">📖</div>
                        <div class="flex-1">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-gray-600">Unlocked Lore Entries</span>
                                <span class="font-bold">{{ $stats['unlocked_lore'] }} / 14</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-purple-600 h-3 rounded-full" style="width: {{ ($stats['unlocked_lore'] / 14) * 100 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
