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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Battle Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-bold">Battle Summary</h3>
                        @if ($match->status === 'completed')
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Winner</div>
                                <div class="text-2xl font-bold text-green-600">
                                    {{ $match->winner === 'attacker' ? $match->attackerTeam->name : $match->defenderTeam->name }}
                                </div>
                                @if ($match->rating_change > 0)
                                    <div class="mt-2 px-3 py-1 bg-green-100 text-green-800 rounded-md inline-block">
                                        +{{ $match->rating_change }} Rating
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-md font-semibold">
                                {{ ucfirst($match->status) }}
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <!-- Attacker Team -->
                        <div class="border-2 rounded-lg p-4 {{ $match->winner === 'attacker' ? 'border-green-500 bg-green-50' : 'border-gray-300' }}">
                            <div class="text-center mb-3">
                                <div class="text-sm text-gray-500 mb-1">
                                    Attacker
                                    @if ($match->attacker)
                                        <a href="{{ route('players.show', $match->attacker) }}" class="text-blue-600 hover:underline ml-1">
                                            ({{ $match->attacker->name }})
                                        </a>
                                    @endif
                                </div>
                                <h4 class="text-xl font-bold">{{ $match->attackerTeam->name }}</h4>
                                @if ($match->winner === 'attacker')
                                    <div class="text-green-600 font-semibold mt-2">🏆 Victory</div>
                                @endif
                            </div>
                            <div class="space-y-2">
                                @foreach ($match->attackerTeam->units as $unit)
                                    <div class="bg-white p-2 rounded border border-gray-200">
                                        <div class="font-semibold text-sm">{{ $unit->name }}</div>
                                        <div class="grid grid-cols-4 gap-1 text-xs mt-1">
                                            <div><span class="text-gray-500">HP:</span> {{ $unit->hp }}</div>
                                            <div><span class="text-gray-500">ATK:</span> {{ $unit->attack }}</div>
                                            <div><span class="text-gray-500">DEF:</span> {{ $unit->defense }}</div>
                                            <div><span class="text-gray-500">SPD:</span> {{ $unit->speed }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Battle Stats -->
                        <div class="flex flex-col justify-center items-center">
                            <div class="text-6xl mb-4">⚔️</div>
                            @if ($match->status === 'completed')
                                <div class="text-center">
                                    <div class="text-sm text-gray-500">Total Turns</div>
                                    <div class="text-3xl font-bold">{{ $match->total_turns }}</div>
                                </div>
                            @else
                                <div class="text-center text-gray-500">
                                    Battle in progress...
                                </div>
                            @endif
                        </div>

                        <!-- Defender Team -->
                        <div class="border-2 rounded-lg p-4 {{ $match->winner === 'defender' ? 'border-green-500 bg-green-50' : 'border-gray-300' }}">
                            <div class="text-center mb-3">
                                <div class="text-sm text-gray-500 mb-1">
                                    Defender
                                    @if ($match->defender)
                                        <a href="{{ route('players.show', $match->defender) }}" class="text-blue-600 hover:underline ml-1">
                                            ({{ $match->defender->name }})
                                        </a>
                                    @else
                                        (AI)
                                    @endif
                                </div>
                                <h4 class="text-xl font-bold">{{ $match->defenderTeam->name }}</h4>
                                @if ($match->winner === 'defender')
                                    <div class="text-green-600 font-semibold mt-2">🏆 Victory</div>
                                @endif
                            </div>
                            <div class="space-y-2">
                                @foreach ($match->defenderTeam->units as $unit)
                                    <div class="bg-white p-2 rounded border border-gray-200">
                                        <div class="font-semibold text-sm">{{ $unit->name }}</div>
                                        <div class="grid grid-cols-4 gap-1 text-xs mt-1">
                                            <div><span class="text-gray-500">HP:</span> {{ $unit->hp }}</div>
                                            <div><span class="text-gray-500">ATK:</span> {{ $unit->attack }}</div>
                                            <div><span class="text-gray-500">DEF:</span> {{ $unit->defense }}</div>
                                            <div><span class="text-gray-500">SPD:</span> {{ $unit->speed }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Battle Log / Replay -->
            @if ($match->status === 'completed' && $match->battleLogs->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-2xl font-bold mb-4">Battle Replay</h3>

                        <div class="space-y-3 max-h-96 overflow-y-auto bg-gray-50 p-4 rounded-lg">
                            @foreach ($match->battleLogs as $log)
                                <div class="bg-white border-l-4 p-3 rounded {{ $log->attacker_team === 'attacker' ? 'border-blue-500' : 'border-red-500' }}">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="text-sm font-semibold mb-1">
                                                <span class="text-gray-500">Turn {{ $log->turn_number }}:</span>
                                                <span class="{{ $log->attacker_team === 'attacker' ? 'text-blue-700' : 'text-red-700' }}">
                                                    {{ $log->attacker_name }}
                                                </span>
                                                <span class="text-gray-500">attacks</span>
                                                <span class="{{ $log->defender_team === 'attacker' ? 'text-blue-700' : 'text-red-700' }}">
                                                    {{ $log->defender_name }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-600">
                                                Dealt <span class="font-bold text-orange-600">{{ $log->damage }}</span> damage
                                                •
                                                {{ $log->defender_name }}'s HP: <span class="font-bold">{{ $log->defender_hp_after }}</span>
                                                @if ($log->defender_hp_after <= 0)
                                                    <span class="text-red-600 font-bold">💀 Defeated!</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-2xl ml-2">
                                            @if ($log->defender_hp_after <= 0)
                                                💀
                                            @else
                                                ⚔️
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 text-sm text-gray-500 text-center">
                            Total of {{ $match->battleLogs->count() }} turns
                        </div>
                    </div>
                </div>
            @elseif ($match->status === 'pending')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">
                        <div class="text-4xl mb-4">⏳</div>
                        <h3 class="text-xl font-semibold mb-2">Battle Processing</h3>
                        <p>Your battle is being processed. Refresh the page to see results.</p>
                        <button
                            onclick="window.location.reload()"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                        >
                            Refresh Page
                        </button>
                    </div>
                </div>
            @endif

            <!-- Delete Battle -->
            @if ($match->status === 'completed')
                <div class="mt-6">
                    <form method="POST" action="{{ route('battles.destroy', $match) }}" onsubmit="return confirm('Are you sure you want to delete this battle record?');">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                        >
                            Delete Battle Record
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
