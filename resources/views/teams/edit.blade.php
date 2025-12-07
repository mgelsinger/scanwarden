<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Team') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="POST" action="{{ route('teams.update', $team) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Team Name
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name', $team->name) }}"
                                required
                                maxlength="255"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter a name for your team"
                            >
                        </div>

                        <div class="flex gap-4">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                            >
                                Update Team
                            </button>
                            <a
                                href="{{ route('teams.show', $team) }}"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                            >
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Team Builder -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Team Builder ({{ $team->units->count() }}/5)</h3>

                    <div class="w-full bg-gray-200 rounded-full h-2 mb-6">
                        <div class="bg-blue-600 h-2 rounded-full transition-all"
                             style="width: {{ ($team->units->count() / 5) * 100 }}%"></div>
                    </div>

                    @if ($team->units->isNotEmpty())
                        <div class="mb-6">
                            <h4 class="font-semibold text-sm text-gray-700 mb-3">Current Units</h4>
                            <div class="space-y-2">
                                @foreach ($team->units as $unit)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex items-center gap-3">
                                            <div>
                                                <div class="font-bold">{{ $unit->name }}</div>
                                                <div class="flex gap-2 mt-1">
                                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold"
                                                          style="background-color: {{ $unit->sector->color }}20; color: {{ $unit->sector->color }};">
                                                        {{ $unit->sector->name }}
                                                    </span>
                                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold text-white"
                                                          style="background-color: {{ config('rarities.tiers.' . $unit->rarity . '.color') }};">
                                                        {{ config('rarities.tiers.' . $unit->rarity . '.name') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('teams.removeUnit', [$team, $unit]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-semibold">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($availableUnits->isNotEmpty() && $team->units->count() < 5)
                        <div>
                            <h4 class="font-semibold text-sm text-gray-700 mb-3">Add Units</h4>
                            <div class="space-y-2">
                                @foreach ($availableUnits as $unit)
                                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200 hover:border-blue-300 transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div>
                                                <div class="font-bold">{{ $unit->name }}</div>
                                                <div class="flex gap-2 mt-1">
                                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold"
                                                          style="background-color: {{ $unit->sector->color }}20; color: {{ $unit->sector->color }};">
                                                        {{ $unit->sector->name }}
                                                    </span>
                                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold text-white"
                                                          style="background-color: {{ config('rarities.tiers.' . $unit->rarity . '.color') }};">
                                                        {{ config('rarities.tiers.' . $unit->rarity . '.name') }}
                                                    </span>
                                                    <span class="text-xs text-gray-600">
                                                        HP: {{ $unit->hp }} | ATK: {{ $unit->attack }} | DEF: {{ $unit->defense }} | SPD: {{ $unit->speed }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('teams.addUnit', $team) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="unit_id" value="{{ $unit->id }}">
                                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-sm font-semibold rounded hover:bg-blue-700">
                                                Add
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif ($team->units->count() >= 5)
                        <p class="text-sm text-gray-600 italic">Team is full. Remove a unit to add a different one.</p>
                    @else
                        <p class="text-sm text-gray-600 italic">No available units. All your units are assigned to teams.</p>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-red-600 mb-4">Danger Zone</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Deleting this team will remove all unit assignments. The units themselves will not be deleted.
                    </p>
                    <form method="POST" action="{{ route('teams.destroy', $team) }}" onsubmit="return confirm('Are you sure you want to delete this team? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                        >
                            Delete Team
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
