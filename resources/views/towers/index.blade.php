<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sector Towers') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <p class="text-gray-600">
                            Test your might against progressively challenging enemies in Sector Towers.
                            Clear floors to earn rewards and prove your mastery!
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @forelse ($towers as $towerData)
                            @php
                                $tower = $towerData['tower'];
                                $highestFloor = $towerData['highest_floor_cleared'];
                                $progressPercentage = $towerData['progress_percentage'];
                            @endphp

                            <div class="border rounded-lg p-6 hover:shadow-lg transition-shadow">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-xl font-bold">{{ $tower->name }}</h3>
                                    <span class="px-3 py-1 rounded text-sm font-semibold"
                                          style="background-color: {{ $tower->sector->color }}; color: white;">
                                        {{ $tower->sector->name }}
                                    </span>
                                </div>

                                @if ($tower->description)
                                    <p class="text-gray-600 mb-4 text-sm">{{ $tower->description }}</p>
                                @endif

                                <div class="mb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-medium text-gray-700">Progress</span>
                                        <span class="text-sm font-bold">{{ $highestFloor }} / {{ $tower->max_floor }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $progressPercentage }}%"></div>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">
                                        @if ($highestFloor === 0)
                                            Not started
                                        @elseif ($highestFloor >= $tower->max_floor)
                                            <span class="text-green-600 font-semibold">✓ Completed</span>
                                        @else
                                            Floor {{ $highestFloor + 1 }} available
                                        @endif
                                    </span>
                                    <a href="{{ route('towers.show', $tower) }}"
                                       class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm font-semibold">
                                        {{ $highestFloor >= $tower->max_floor ? 'View' : 'Enter' }}
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-3 text-center text-gray-500 py-8">
                                <p>No towers available at this time.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
