<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Quests & Achievements') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Daily Missions Section -->
            <div class="mb-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Daily Missions</h3>

                @if($dailyQuests->isEmpty())
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <p class="text-gray-500 text-center">No daily missions available. Check back tomorrow!</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($dailyQuests as $userQuest)
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="p-6">
                                    <div class="flex justify-between items-center mb-3">
                                        <div class="flex-1">
                                            <h4 class="text-lg font-semibold text-indigo-600">{{ $userQuest->quest->name }}</h4>
                                            <p class="mt-1 text-sm text-gray-600">{{ $userQuest->quest->description }}</p>
                                        </div>
                                        <div class="ml-4">
                                            @if($userQuest->is_completed)
                                                <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">Completed</span>
                                            @else
                                                <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">In Progress</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>Progress</span>
                                            <span class="font-semibold">{{ $userQuest->progress }} / {{ $userQuest->target_value }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                            @php
                                                $pct = $userQuest->target_value > 0
                                                    ? min(100, (int) round(($userQuest->progress / $userQuest->target_value) * 100))
                                                    : 0;
                                            @endphp
                                            <div class="h-full bg-indigo-600 transition-all duration-300" style="width: {{ $pct }}%;"></div>
                                        </div>
                                    </div>

                                    @if($userQuest->quest->is_daily && $userQuest->expires_at)
                                        <div class="mt-3 text-xs text-gray-500">
                                            <span class="font-semibold">Expires:</span> {{ $userQuest->expires_at->diffForHumans() }}
                                        </div>
                                    @endif

                                    @if($userQuest->quest->reward_payload)
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <div class="text-sm font-semibold text-gray-700 mb-2">Rewards:</div>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($userQuest->quest->reward_payload as $reward)
                                                    @if($reward['type'] === 'essence')
                                                        <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-lg text-xs font-semibold">
                                                            {{ $reward['amount'] }} {{ ucfirst($reward['essence_type']) }} Essence
                                                        </span>
                                                    @elseif($reward['type'] === 'sector_energy')
                                                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-semibold">
                                                            {{ $reward['amount'] }} Sector Energy
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Achievements Section -->
            <div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Achievements</h3>

                @if($achievements->isEmpty())
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <p class="text-gray-500 text-center">No achievements tracked yet.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($achievements as $userQuest)
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="p-6">
                                    <div class="flex justify-between items-center mb-3">
                                        <div class="flex-1">
                                            <h4 class="text-lg font-semibold text-gray-900">{{ $userQuest->quest->name }}</h4>
                                            <p class="mt-1 text-sm text-gray-600">{{ $userQuest->quest->description }}</p>
                                        </div>
                                        <div class="ml-4">
                                            @if($userQuest->is_completed)
                                                <div class="text-center">
                                                    <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">✓ Completed</span>
                                                    @if($userQuest->completed_at)
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            {{ $userQuest->completed_at->format('M d, Y') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-center">
                                                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">Locked</span>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        {{ $userQuest->target_value - $userQuest->progress }} remaining
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>Progress</span>
                                            <span class="font-semibold">{{ $userQuest->progress }} / {{ $userQuest->target_value }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                            @php
                                                $pct = $userQuest->target_value > 0
                                                    ? min(100, (int) round(($userQuest->progress / $userQuest->target_value) * 100))
                                                    : 0;
                                            @endphp
                                            <div class="h-full {{ $userQuest->is_completed ? 'bg-emerald-600' : 'bg-gray-600' }} transition-all duration-300" style="width: {{ $pct }}%;"></div>
                                        </div>
                                    </div>

                                    @if($userQuest->quest->reward_payload && !$userQuest->is_completed)
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <div class="text-sm font-semibold text-gray-700 mb-2">Rewards:</div>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($userQuest->quest->reward_payload as $reward)
                                                    @if($reward['type'] === 'essence')
                                                        <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-lg text-xs font-semibold">
                                                            {{ $reward['amount'] }} {{ ucfirst($reward['essence_type']) }} Essence
                                                        </span>
                                                    @elseif($reward['type'] === 'sector_energy')
                                                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-semibold">
                                                            {{ $reward['amount'] }} Sector Energy
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
