<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $tower->name }}
            </h2>
            <a href="{{ route('towers.index') }}" class="text-blue-600 hover:text-blue-800">
                ← Back to Towers
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Success/Error Messages --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                    @if (session('tower_result') && session('tower_result')['first_clear'] && !empty(session('tower_result')['rewards']))
                        <div class="mt-2">
                            <strong>Rewards earned:</strong>
                            @foreach (session('tower_result')['rewards'] as $reward)
                                <span class="inline-block bg-green-200 px-2 py-1 rounded text-sm mr-2">
                                    {{ $reward }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            {{-- Tower Info --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">{{ $tower->name }}</h3>
                            <p class="text-gray-600">{{ $tower->description }}</p>
                        </div>
                        <span class="px-4 py-2 rounded text-lg font-semibold"
                              style="background-color: {{ $tower->sector->color }}; color: white;">
                            {{ $tower->sector->name }}
                        </span>
                    </div>

                    <div class="mt-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Your Progress</span>
                            <span class="text-lg font-bold">Floor {{ $progress->highest_floor_cleared }} / {{ $tower->max_floor }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full"
                                 style="width: {{ ($progress->highest_floor_cleared / $tower->max_floor) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stages List --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-4">Tower Floors</h3>

                    <div class="space-y-4">
                        @forelse ($stages as $stageData)
                            @php
                                $stage = $stageData['stage'];
                                $status = $stageData['status'];
                                $locked = $stageData['locked'];
                                $cleared = $stageData['cleared'];
                            @endphp

                            <div class="border rounded-lg p-4 {{ $locked ? 'bg-gray-100 opacity-60' : '' }} {{ $status === 'current' ? 'border-blue-500 border-2' : '' }}">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h4 class="text-lg font-bold">Floor {{ $stage->floor }}</h4>

                                            @if ($cleared)
                                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">
                                                    ✓ Cleared
                                                </span>
                                            @elseif ($locked)
                                                <span class="px-2 py-1 bg-gray-300 text-gray-700 rounded text-xs font-semibold">
                                                    🔒 Locked
                                                </span>
                                            @else
                                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold">
                                                    Available
                                                </span>
                                            @endif
                                        </div>

                                        <div class="text-sm text-gray-600 mb-2">
                                            <strong>Recommended Power:</strong> {{ $stage->recommended_power }}
                                        </div>

                                        @if ($stage->rewards)
                                            <div class="text-sm text-gray-600">
                                                <strong>First Clear Rewards:</strong>
                                                @foreach ($stage->rewards as $reward)
                                                    <span class="inline-block bg-gray-200 px-2 py-1 rounded text-xs mr-1">
                                                        @if ($reward['type'] === 'essence')
                                                            {{ ucfirst($reward['essence_type']) }} Essence: {{ $reward['amount'] }}
                                                        @elseif ($reward['type'] === 'sector_energy')
                                                            Energy: {{ $reward['amount'] }}
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <div class="ml-4">
                                        @if (!$locked)
                                            <form method="POST" action="{{ route('towers.fight', [$tower, $stage->floor]) }}" class="inline">
                                                @csrf
                                                <div class="mb-2">
                                                    <select name="team_id" required class="border rounded px-3 py-2 text-sm">
                                                        <option value="">Select Team</option>
                                                        @foreach ($teams as $team)
                                                            <option value="{{ $team->id }}">
                                                                {{ $team->name }} ({{ $team->units->count() }} units)
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button type="submit"
                                                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm font-semibold w-full">
                                                    {{ $cleared ? 'Fight Again' : 'Challenge' }}
                                                </button>
                                            </form>
                                        @else
                                            <button disabled class="px-4 py-2 bg-gray-300 text-gray-600 rounded text-sm font-semibold cursor-not-allowed">
                                                Locked
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-gray-500 py-8">
                                <p>No floors available for this tower.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
