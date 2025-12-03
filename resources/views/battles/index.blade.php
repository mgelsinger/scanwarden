<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Battle History') }}
            </h2>
            <a href="{{ route('battles.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                New Battle
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

            @if ($matches->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">
                        <div class="text-6xl mb-4">⚔️</div>
                        <h3 class="text-xl font-semibold mb-2">No Battles Yet</h3>
                        <p class="mb-4">Start your first battle to test your teams!</p>
                        <a href="{{ route('battles.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Start First Battle
                        </a>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($matches as $match)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
                            <div class="p-6">
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-4 mb-2">
                                            <div class="text-center {{ $match->winner === 'attacker' ? 'font-bold' : '' }}">
                                                <div class="text-sm text-gray-500">Attacker</div>
                                                <div class="text-lg {{ $match->winner === 'attacker' ? 'text-green-600' : 'text-gray-700' }}">
                                                    {{ $match->attackerTeam->name }}
                                                </div>
                                            </div>

                                            <div class="text-2xl text-gray-400">⚔️</div>

                                            <div class="text-center {{ $match->winner === 'defender' ? 'font-bold' : '' }}">
                                                <div class="text-sm text-gray-500">Defender</div>
                                                <div class="text-lg {{ $match->winner === 'defender' ? 'text-green-600' : 'text-gray-700' }}">
                                                    {{ $match->defenderTeam->name }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-4 text-sm text-gray-500">
                                            <span>Status:
                                                @if ($match->status === 'completed')
                                                    <span class="text-green-600 font-semibold">Completed</span>
                                                @elseif ($match->status === 'pending')
                                                    <span class="text-yellow-600 font-semibold">Processing...</span>
                                                @else
                                                    <span class="text-gray-600 font-semibold">{{ ucfirst($match->status) }}</span>
                                                @endif
                                            </span>

                                            @if ($match->status === 'completed')
                                                <span>Winner:
                                                    <span class="font-semibold text-green-600">
                                                        {{ $match->winner === 'attacker' ? $match->attackerTeam->name : $match->defenderTeam->name }}
                                                    </span>
                                                </span>
                                                <span>Turns: <span class="font-semibold">{{ $match->total_turns }}</span></span>
                                            @endif

                                            <span class="ml-auto">{{ $match->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>

                                    <div class="flex gap-2 ml-4">
                                        @if ($match->status === 'completed')
                                            <a href="{{ route('battles.show', $match) }}"
                                               class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 text-sm font-semibold">
                                                View Replay
                                            </a>
                                        @else
                                            <a href="{{ route('battles.show', $match) }}"
                                               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-semibold">
                                                View Details
                                            </a>
                                        @endif
                                    </div>
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
</x-app-layout>
