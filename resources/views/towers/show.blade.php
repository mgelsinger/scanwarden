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
            {{-- Tower Info --}}
            <x-card class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold mb-2 text-gray-100">{{ $tower->name }}</h3>
                        <p class="text-gray-400 text-xs">{{ $tower->description }}</p>
                    </div>
                    <x-badge variant="info">{{ $tower->sector->name }}</x-badge>
                </div>

                <div class="mt-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-gray-300">Your Progress</span>
                        <span class="text-sm font-bold text-gray-200">Floor {{ $progress->highest_floor_cleared }} / {{ $tower->max_floor }}</span>
                    </div>
                    <x-progress-bar
                        :value="$progress->highest_floor_cleared"
                        :max="$tower->max_floor"
                    />
                </div>
            </x-card>

            {{-- Stages List --}}
            <x-card>
                <h3 class="text-sm font-bold mb-4 text-gray-100">Tower Floors</h3>

                    <div class="space-y-4">
                        @forelse ($stages as $stageData)
                            @php
                                $stage = $stageData['stage'];
                                $status = $stageData['status'];
                                $locked = $stageData['locked'];
                                $cleared = $stageData['cleared'];
                            @endphp

                            <div class="border {{ $locked ? 'border-gray-700 opacity-60' : 'border-gray-600' }} {{ $status === 'current' ? 'border-indigo-500 border-2' : '' }} rounded-lg p-4 bg-gray-800/40">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h4 class="text-sm font-bold text-gray-100">Floor {{ $stage->floor }}</h4>

                                            @if ($cleared)
                                                <x-badge variant="success">✓ Cleared</x-badge>
                                            @elseif ($locked)
                                                <x-badge variant="default">🔒 Locked</x-badge>
                                            @else
                                                <x-badge variant="info">Available</x-badge>
                                            @endif
                                        </div>

                                        <div class="text-xs text-gray-400 mb-2">
                                            <strong>Recommended Power:</strong> {{ $stage->recommended_power }}
                                        </div>

                                        @if ($stage->rewards)
                                            <div class="text-xs text-gray-400">
                                                <strong>First Clear Rewards:</strong>
                                                <div class="flex flex-wrap gap-1 mt-1">
                                                    @foreach ($stage->rewards as $reward)
                                                        @if ($reward['type'] === 'essence')
                                                            <x-badge variant="info">
                                                                {{ ucfirst($reward['essence_type']) }} Essence: {{ $reward['amount'] }}
                                                            </x-badge>
                                                        @elseif ($reward['type'] === 'sector_energy')
                                                            <x-badge variant="info">
                                                                Energy: {{ $reward['amount'] }}
                                                            </x-badge>
                                                        @endif
                                                    @endforeach
                                                </div>
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
                            <div class="text-center text-gray-400 py-8">
                                <p class="text-sm">No floors available for this tower.</p>
                            </div>
                        @endforelse
                    </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
