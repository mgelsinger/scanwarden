<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Leaderboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($currentUserRank)
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-300 rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">Your Rank</h3>
                            <div class="flex items-center gap-4">
                                <div class="text-4xl font-bold text-blue-600">#{{ $currentUserRank }}</div>
                                <div>
                                    <div class="text-2xl font-semibold" style="color: {{ auth()->user()->tier_color ?? '#CD7F32' }}">
                                        {{ auth()->user()->rating ?? 1200 }} Rating
                                    </div>
                                    <div class="text-sm font-semibold px-3 py-1 rounded inline-block mt-1"
                                         style="background-color: {{ auth()->user()->tier_color ?? '#CD7F32' }}20; color: {{ auth()->user()->tier_color ?? '#CD7F32' }};">
                                        {{ auth()->user()->tier ?? 'Gold' }} Tier
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-6xl">🏆</div>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-2xl font-bold mb-6">Top Players</h3>

                    @if ($leaders->isEmpty())
                        <div class="text-center text-gray-500 py-12">
                            <div class="text-6xl mb-4">🏆</div>
                            <h3 class="text-xl font-semibold mb-2">No Rankings Yet</h3>
                            <p class="mb-4">Be the first to earn rating points by battling!</p>
                            <a href="{{ route('battles.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Start First Battle
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Rank
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Player
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Rating
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Tier
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Win Rate
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Battles
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($leaders as $index => $entry)
                                        @php
                                            $user = $entry['user'];
                                            $stats = $entry['stats'];
                                            $tier = $entry['tier'];
                                            $tierColor = $entry['tier_color'];
                                        @endphp
                                        <tr class="{{ auth()->id() === $user->id ? 'bg-blue-50' : 'hover:bg-gray-50' }}">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    @if ($index === 0)
                                                        <span class="text-3xl mr-2">🥇</span>
                                                    @elseif ($index === 1)
                                                        <span class="text-3xl mr-2">🥈</span>
                                                    @elseif ($index === 2)
                                                        <span class="text-3xl mr-2">🥉</span>
                                                    @endif
                                                    <div class="text-lg font-bold text-gray-900">
                                                        #{{ $index + 1 }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <a href="{{ route('players.show', $user) }}" class="text-blue-600 hover:underline">
                                                            {{ $user->name }}
                                                        </a>
                                                        @if (auth()->id() === $user->id)
                                                            <span class="ml-2 text-xs text-blue-600">(You)</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-lg font-bold" style="color: {{ $tierColor }}">
                                                    {{ $user->rating ?? 0 }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold"
                                                      style="background-color: {{ $tierColor }}20; color: {{ $tierColor }};">
                                                    {{ $tier }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ $stats['win_rate'] }}%
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ $stats['total_battles'] }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-2">Rating Tiers</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2 text-sm">
                    <div class="p-2 rounded text-center font-semibold" style="background-color: #FFD70020; color: #FFD700;">
                        Legend (2000+)
                    </div>
                    <div class="p-2 rounded text-center font-semibold" style="background-color: #9B59B620; color: #9B59B6;">
                        Master (1800+)
                    </div>
                    <div class="p-2 rounded text-center font-semibold" style="background-color: #3498DB20; color: #3498DB;">
                        Diamond (1600+)
                    </div>
                    <div class="p-2 rounded text-center font-semibold" style="background-color: #1ABC9C20; color: #1ABC9C;">
                        Platinum (1400+)
                    </div>
                    <div class="p-2 rounded text-center font-semibold" style="background-color: #F39C1220; color: #F39C12;">
                        Gold (1200+)
                    </div>
                    <div class="p-2 rounded text-center font-semibold" style="background-color: #95A5A620; color: #95A5A6;">
                        Silver (1000+)
                    </div>
                    <div class="p-2 rounded text-center font-semibold" style="background-color: #CD7F3220; color: #CD7F32;">
                        Bronze (&lt;1000)
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
