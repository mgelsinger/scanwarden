<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Start New Battle') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($teams->count() < 2)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-4">
                    You need at least 2 teams to start a battle. Please create more teams first.
                    <a href="{{ route('teams.create') }}" class="underline font-semibold">Create a team</a>
                </div>
            @endif

            @if ($teams->where('units_count', 0)->count() > 0)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-4">
                    Some teams have no units. Make sure to add units to your teams before battling.
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('battles.store') }}">
                        @csrf

                        <div class="mb-6">
                            <label for="attacker_team_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Attacker Team
                            </label>
                            <select
                                id="attacker_team_id"
                                name="attacker_team_id"
                                required
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select attacker team...</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" {{ old('attacker_team_id') == $team->id ? 'selected' : '' }}>
                                        {{ $team->name }} ({{ $team->units_count }} units)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-6">
                            <label for="defender_team_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Defender Team
                            </label>
                            <select
                                id="defender_team_id"
                                name="defender_team_id"
                                required
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select defender team...</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" {{ old('defender_team_id') == $team->id ? 'selected' : '' }}>
                                        {{ $team->name }} ({{ $team->units_count }} units)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex gap-4">
                            <button
                                type="submit"
                                {{ $teams->count() < 2 ? 'disabled' : '' }}
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest {{ $teams->count() < 2 ? 'bg-gray-400 cursor-not-allowed' : 'bg-gray-800 hover:bg-gray-700' }}"
                            >
                                Start Battle
                            </button>
                            <a
                                href="{{ route('battles.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                            >
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Battle Mechanics</h3>
                <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                    <li>Turn-based combat system</li>
                    <li>Unit with highest speed attacks first each turn</li>
                    <li>Damage = Attacker's Attack - Defender's Defense (minimum 1)</li>
                    <li>Battle ends when all units of one team are defeated</li>
                    <li>Maximum 100 turns (winner determined by remaining HP if timeout)</li>
                </ul>
            </div>

            @if ($teams->isNotEmpty())
                <div class="mt-6">
                    <h3 class="text-lg font-semibold mb-4">Your Teams</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($teams as $team)
                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-bold">{{ $team->name }}</h4>
                                    <span class="text-sm text-gray-500">{{ $team->units_count }}/5 units</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full"
                                         style="width: {{ ($team->units_count / 5) * 100 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
