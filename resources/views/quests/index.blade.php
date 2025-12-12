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
                <h3 class="text-2xl font-bold text-gray-100 mb-4">Daily Missions</h3>

                @if($dailyQuests->isEmpty())
                    <x-card>
                        <p class="text-gray-400 text-center text-sm">No daily missions available. Check back tomorrow!</p>
                    </x-card>
                @else
                    <div class="space-y-4">
                        @foreach($dailyQuests as $userQuest)
                            <x-card>
                                <div class="flex justify-between items-center mb-3">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold text-indigo-400">{{ $userQuest->quest->name }}</h4>
                                        <p class="mt-1 text-xs text-gray-400">{{ $userQuest->quest->description }}</p>
                                    </div>
                                    <div class="ml-4">
                                        @if($userQuest->is_completed)
                                            <x-badge variant="success">Completed</x-badge>
                                        @else
                                            <x-badge variant="warning">In Progress</x-badge>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                                        <span>Progress</span>
                                        <span class="font-semibold">{{ $userQuest->progress }} / {{ $userQuest->target_value }}</span>
                                    </div>
                                    <x-progress-bar
                                        :value="$userQuest->progress"
                                        :max="$userQuest->target_value"
                                    />
                                </div>

                                @if($userQuest->quest->is_daily && $userQuest->expires_at)
                                    <div class="mt-3 text-xs text-gray-400">
                                        <span class="font-semibold">Expires:</span> {{ $userQuest->expires_at->diffForHumans() }}
                                    </div>
                                @endif

                                @if($userQuest->quest->reward_payload)
                                    <div class="mt-4 pt-4 border-t border-gray-700">
                                        <div class="text-xs font-semibold text-gray-300 mb-2">Rewards:</div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($userQuest->quest->reward_payload as $reward)
                                                @if($reward['type'] === 'essence')
                                                    <x-badge variant="info">
                                                        {{ $reward['amount'] }} {{ ucfirst($reward['essence_type']) }} Essence
                                                    </x-badge>
                                                @elseif($reward['type'] === 'sector_energy')
                                                    <x-badge variant="info">
                                                        {{ $reward['amount'] }} Sector Energy
                                                    </x-badge>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </x-card>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Achievements Section -->
            <div>
                <h3 class="text-2xl font-bold text-gray-100 mb-4">Achievements</h3>

                @if($achievements->isEmpty())
                    <x-card>
                        <p class="text-gray-400 text-center text-sm">No achievements tracked yet.</p>
                    </x-card>
                @else
                    <div class="space-y-4">
                        @foreach($achievements as $userQuest)
                            <x-card>
                                <div class="flex justify-between items-center mb-3">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold text-gray-100">{{ $userQuest->quest->name }}</h4>
                                        <p class="mt-1 text-xs text-gray-400">{{ $userQuest->quest->description }}</p>
                                    </div>
                                    <div class="ml-4">
                                        @if($userQuest->is_completed)
                                            <div class="text-center">
                                                <x-badge variant="success">✓ Completed</x-badge>
                                                @if($userQuest->completed_at)
                                                    <div class="text-[10px] text-gray-400 mt-1">
                                                        {{ $userQuest->completed_at->format('M d, Y') }}
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="text-center">
                                                <x-badge variant="default">Locked</x-badge>
                                                <div class="text-[10px] text-gray-400 mt-1">
                                                    {{ $userQuest->target_value - $userQuest->progress }} remaining
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                                        <span>Progress</span>
                                        <span class="font-semibold">{{ $userQuest->progress }} / {{ $userQuest->target_value }}</span>
                                    </div>
                                    <x-progress-bar
                                        :value="$userQuest->progress"
                                        :max="$userQuest->target_value"
                                    />
                                </div>

                                @if($userQuest->quest->reward_payload && !$userQuest->is_completed)
                                    <div class="mt-4 pt-4 border-t border-gray-700">
                                        <div class="text-xs font-semibold text-gray-300 mb-2">Rewards:</div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($userQuest->quest->reward_payload as $reward)
                                                @if($reward['type'] === 'essence')
                                                    <x-badge variant="info">
                                                        {{ $reward['amount'] }} {{ ucfirst($reward['essence_type']) }} Essence
                                                    </x-badge>
                                                @elseif($reward['type'] === 'sector_energy')
                                                    <x-badge variant="info">
                                                        {{ $reward['amount'] }} Sector Energy
                                                    </x-badge>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </x-card>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
