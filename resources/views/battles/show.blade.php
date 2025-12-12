<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Battle Details
            </h2>
            <a href="{{ route('battles.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                Back to Battles
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Battle Result Card -->
            <x-card title="Battle Result">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        @php
                            $userWon = ($perspective === 'attacker' && $match->winner === 'attacker') ||
                                       ($perspective === 'defender' && $match->winner === 'defender');
                        @endphp

                        <x-badge :variant="$userWon ? 'success' : 'danger'">
                            {{ $userWon ? 'Victory' : 'Defeat' }}
                        </x-badge>

                        @if($match->status !== 'completed')
                            <x-badge variant="warning" class="ml-2">
                                {{ ucfirst($match->status) }}
                            </x-badge>
                        @endif
                    </div>

                    <div class="text-right">
                        <div class="text-xs text-gray-400">Battle Type</div>
                        <div class="text-sm font-semibold text-gray-200">
                            @if($match->defender_id === null)
                                Practice / Tower
                            @else
                                PvP Match
                            @endif
                        </div>

                        @if($match->rating_change && $match->rating_change != 0)
                            <div class="mt-2">
                                <x-badge :variant="$match->rating_change > 0 ? 'success' : 'danger'">
                                    {{ $match->rating_change > 0 ? '+' : '' }}{{ $match->rating_change }} Rating
                                </x-badge>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Attacker Team -->
                    <div class="border-2 rounded-lg p-4 {{ $match->winner === 'attacker' ? 'border-emerald-500 bg-emerald-900/20' : 'border-gray-700' }}">
                        <div class="text-center mb-3">
                            <div class="text-xs text-gray-400 mb-1">
                                Attacker
                                @if($match->defender_id)
                                    <span class="text-gray-300 ml-1">
                                        ({{ $match->attacker->name ?? 'Unknown' }})
                                    </span>
                                @endif
                            </div>
                            <h4 class="text-lg font-bold text-gray-100">{{ $match->attackerTeam->name ?? 'Team' }}</h4>
                            @if($match->winner === 'attacker')
                                <div class="text-emerald-400 font-semibold mt-2 text-sm">🏆 Victory</div>
                            @endif
                        </div>
                        <div class="space-y-2">
                            @foreach($match->attackerTeam->units ?? [] as $unit)
                                <div class="bg-gray-800/50 p-2 rounded border border-gray-700">
                                    <div class="font-semibold text-xs text-gray-200">{{ $unit->name }}</div>
                                    <div class="grid grid-cols-4 gap-1 text-[10px] mt-1 text-gray-400">
                                        <div>HP: {{ $unit->hp }}</div>
                                        <div>ATK: {{ $unit->attack }}</div>
                                        <div>DEF: {{ $unit->defense }}</div>
                                        <div>SPD: {{ $unit->speed }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Battle Stats -->
                    <div class="flex flex-col justify-center items-center">
                        <div class="text-6xl mb-4">⚔️</div>
                        @if($match->status === 'completed')
                            <div class="text-center">
                                <div class="text-xs text-gray-400">Total Turns</div>
                                <div class="text-3xl font-bold text-gray-100">{{ $match->total_turns }}</div>
                                <div class="mt-3 space-y-1">
                                    <div class="text-xs text-gray-400">
                                        Attacker Survivors: <span class="text-gray-200">{{ $match->attacker_survivors ?? 0 }}</span>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        Defender Survivors: <span class="text-gray-200">{{ $match->defender_survivors ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center text-gray-400 text-sm">
                                Battle in progress...
                            </div>
                        @endif
                    </div>

                    <!-- Defender Team -->
                    <div class="border-2 rounded-lg p-4 {{ $match->winner === 'defender' ? 'border-emerald-500 bg-emerald-900/20' : 'border-gray-700' }}">
                        <div class="text-center mb-3">
                            <div class="text-xs text-gray-400 mb-1">
                                Defender
                                @if($match->defender_id)
                                    <span class="text-gray-300 ml-1">
                                        ({{ $match->defender->name ?? 'Unknown' }})
                                    </span>
                                @else
                                    <span class="text-gray-300 ml-1">(AI)</span>
                                @endif
                            </div>
                            <h4 class="text-lg font-bold text-gray-100">{{ $match->defenderTeam->name ?? 'Enemy Team' }}</h4>
                            @if($match->winner === 'defender')
                                <div class="text-emerald-400 font-semibold mt-2 text-sm">🏆 Victory</div>
                            @endif
                        </div>
                        <div class="space-y-2">
                            @foreach($match->defenderTeam->units ?? [] as $unit)
                                <div class="bg-gray-800/50 p-2 rounded border border-gray-700">
                                    <div class="font-semibold text-xs text-gray-200">{{ $unit->name }}</div>
                                    <div class="grid grid-cols-4 gap-1 text-[10px] mt-1 text-gray-400">
                                        <div>HP: {{ $unit->hp }}</div>
                                        <div>ATK: {{ $unit->attack }}</div>
                                        <div>DEF: {{ $unit->defense }}</div>
                                        <div>SPD: {{ $unit->speed }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Battle Log / Replay -->
            @if($match->status === 'completed' && $match->battleLogs->isNotEmpty())
                <x-card title="Battle Replay" subtitle="Turn-by-turn action log">
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($match->battleLogs as $log)
                            <div class="bg-gray-800/30 border-l-4 p-3 rounded {{ $log->attacker_team === 'attacker' ? 'border-indigo-500' : 'border-red-500' }}">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="text-xs font-semibold mb-1 text-gray-200">
                                            <span class="text-gray-400">Turn {{ $log->turn_number }}:</span>
                                            <span class="{{ $log->attacker_team === 'attacker' ? 'text-indigo-300' : 'text-red-300' }}">
                                                {{ $log->attacker_name }}
                                            </span>
                                            <span class="text-gray-400">attacks</span>
                                            <span class="{{ $log->defender_team === 'attacker' ? 'text-indigo-300' : 'text-red-300' }}">
                                                {{ $log->defender_name }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-gray-400">
                                            Dealt <span class="font-bold text-orange-400">{{ $log->damage }}</span> damage
                                            •
                                            {{ $log->defender_name }}'s HP: <span class="font-bold text-gray-200">{{ $log->defender_hp_after }}</span>
                                            @if($log->defender_hp_after <= 0)
                                                <span class="text-red-400 font-bold">💀 Defeated!</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-xl ml-2">
                                        @if($log->defender_hp_after <= 0)
                                            💀
                                        @else
                                            ⚔️
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 text-xs text-gray-400 text-center">
                        Total of {{ $match->battleLogs->count() }} turns
                    </div>
                </x-card>
            @elseif($match->status === 'pending')
                <x-card title="Battle Processing">
                    <div class="text-center text-gray-300 py-4">
                        <div class="text-4xl mb-4">⏳</div>
                        <h3 class="text-lg font-semibold mb-2">Battle in Progress</h3>
                        <p class="text-sm text-gray-400">Your battle is being processed. Refresh the page to see results.</p>
                        <button
                            onclick="window.location.reload()"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-md font-semibold text-xs text-white uppercase tracking-widest"
                        >
                            Refresh Page
                        </button>
                    </div>
                </x-card>
            @endif

            <!-- Delete Battle -->
            @if($match->status === 'completed')
                <div class="flex justify-end">
                    <form method="POST" action="{{ route('battles.destroy', $match) }}" onsubmit="return confirm('Are you sure you want to delete this battle record?');">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-red-600/80 hover:bg-red-600 border border-red-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest"
                        >
                            Delete Battle Record
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
