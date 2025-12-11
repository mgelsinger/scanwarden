<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Battles') }}
            </h2>
            <div class="flex items-center gap-4">
                <a href="{{ route('players.show', auth()->user()) }}" class="text-sm text-blue-600 hover:underline">
                    View Your Profile
                </a>
                <div class="text-sm text-gray-600">
                    Rating: <span class="font-bold text-lg text-indigo-600">{{ $rating }}</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Your Teams Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Your Teams</h3>

                    @if ($teams->isEmpty())
                        <div class="text-center text-gray-500 py-6">
                            <p class="mb-4">You don't have any teams yet!</p>
                            <a href="{{ route('teams.create') }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Create Your First Team
                            </a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($teams as $team)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h4 class="font-bold text-lg">{{ $team->name }}</h4>
                                            <p class="text-sm text-gray-500">{{ $team->units_count }}/5 units</p>
                                        </div>
                                    </div>

                                    @if ($team->units_count > 0)
                                        <div class="flex gap-2">
                                            <form method="POST" action="{{ route('battles.practice') }}" class="flex-1">
                                                @csrf
                                                <input type="hidden" name="team_id" value="{{ $team->id }}">
                                                <input type="hidden" name="difficulty" value="medium">
                                                <button type="submit"
                                                        class="w-full px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-semibold">
                                                    Practice Battle
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('battles.pvp') }}" class="flex-1">
                                                @csrf
                                                <input type="hidden" name="team_id" value="{{ $team->id }}">
                                                <button type="submit"
                                                        class="w-full px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm font-semibold">
                                                    PvP Battle
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <div class="text-sm text-gray-500 italic">
                                            Add units to this team to battle
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-900 mb-2">Battle Types</h4>
                            <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                                <li><span class="font-semibold">Practice Battle:</span> Fight against AI opponents. No rating changes.</li>
                                <li><span class="font-semibold">PvP Battle:</span> Fight against other players' teams. Win: +10 rating, Lose: -5 rating, Draw: 0.</li>
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Battle History -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Recent Battles</h3>

                    @if ($matches->isEmpty())
                        <div class="text-center text-gray-500 py-6">
                            <div class="text-6xl mb-4">⚔️</div>
                            <p>No battles yet. Start your first battle above!</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($matches as $match)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-4 mb-2">
                                                <!-- Attacker Team -->
                                                <div class="text-center {{ $match->winner === 'attacker' ? 'font-bold' : '' }}">
                                                    <div class="text-sm text-gray-500">
                                                        @if ($match->attacker_id)
                                                            {{ $match->attacker->name ?? 'You' }}
                                                        @else
                                                            You
                                                        @endif
                                                    </div>
                                                    <div class="text-lg {{ $match->winner === 'attacker' ? 'text-green-600' : 'text-gray-700' }}">
                                                        {{ $match->attackerTeam->name }}
                                                    </div>
                                                </div>

                                                <div class="text-2xl text-gray-400">⚔️</div>

                                                <!-- Defender Team -->
                                                <div class="text-center {{ $match->winner === 'defender' ? 'font-bold' : '' }}">
                                                    <div class="text-sm text-gray-500">
                                                        @if ($match->defender_id && $match->defender)
                                                            <a href="{{ route('players.show', $match->defender) }}" class="text-blue-600 hover:underline">
                                                                {{ $match->defender->name }}
                                                            </a>
                                                        @else
                                                            AI Dummy Team
                                                        @endif
                                                    </div>
                                                    <div class="text-lg {{ $match->winner === 'defender' ? 'text-green-600' : 'text-gray-700' }}">
                                                        {{ $match->defenderTeam ? $match->defenderTeam->name : 'Training Dummies' }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                                @if ($match->status === 'completed')
                                                    <span>Result:
                                                        <span class="font-semibold {{ $match->winner === 'attacker' ? 'text-green-600' : ($match->winner === 'draw' ? 'text-yellow-600' : 'text-red-600') }}">
                                                            @if ($match->winner === 'attacker')
                                                                Victory
                                                            @elseif ($match->winner === 'defender')
                                                                Defeat
                                                            @else
                                                                Draw
                                                            @endif
                                                        </span>
                                                    </span>

                                                    <span>Turns: <span class="font-semibold">{{ $match->total_turns }}</span></span>

                                                    @if ($match->rating_change != 0)
                                                        <span>Rating:
                                                            <span class="font-semibold {{ $match->rating_change > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                                {{ $match->rating_change > 0 ? '+' : '' }}{{ $match->rating_change }}
                                                            </span>
                                                        </span>
                                                    @else
                                                        <span class="text-gray-500">No rating change</span>
                                                    @endif
                                                @else
                                                    <span class="text-yellow-600 font-semibold">{{ ucfirst($match->status) }}</span>
                                                @endif

                                                <span class="ml-auto">{{ $match->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>

                                        <div class="ml-4">
                                            @if ($match->status === 'completed')
                                                <a href="{{ route('battles.show', $match) }}"
                                                   class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 text-sm font-semibold">
                                                    View Replay
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $matches->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
