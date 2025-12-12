<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sector Towers') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <div class="mb-6">
                    <p class="text-gray-300 text-sm">
                        Test your might against progressively challenging enemies in Sector Towers.
                        Clear floors to earn rewards and prove your mastery!
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse ($towers as $towerData)
                        @php
                            $tower = $towerData['tower'];
                            $highestFloor = $towerData['highest_floor_cleared'];
                            $progressPercentage = $towerData['progress_percentage'];
                        @endphp

                        <x-card class="hover:bg-gray-800/70 transition-colors">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-bold text-gray-100">{{ $tower->name }}</h3>
                                <x-badge variant="info">{{ $tower->sector->name }}</x-badge>
                            </div>

                            @if ($tower->description)
                                <p class="text-gray-400 mb-4 text-xs">{{ $tower->description }}</p>
                            @endif

                            <div class="mb-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs font-medium text-gray-300">Progress</span>
                                    <span class="text-xs font-bold text-gray-200">{{ $highestFloor }} / {{ $tower->max_floor }}</span>
                                </div>
                                <x-progress-bar
                                    :value="$highestFloor"
                                    :max="$tower->max_floor"
                                />
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-400">
                                    @if ($highestFloor === 0)
                                        Not started
                                    @elseif ($highestFloor >= $tower->max_floor)
                                        <x-badge variant="success">✓ Completed</x-badge>
                                    @else
                                        Floor {{ $highestFloor + 1 }} available
                                    @endif
                                </span>
                                <a href="{{ route('towers.show', $tower) }}"
                                   class="px-3 py-1.5 bg-indigo-600 text-white rounded hover:bg-indigo-500 text-xs font-semibold">
                                    {{ $highestFloor >= $tower->max_floor ? 'View' : 'Enter' }}
                                </a>
                            </div>
                        </x-card>
                    @empty
                        <div class="col-span-3 text-center text-gray-400 py-8">
                            <p class="text-sm">No towers available at this time.</p>
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
