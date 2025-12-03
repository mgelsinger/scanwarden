<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $team->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('teams.edit', $team) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                    Edit Team
                </a>
                <a href="{{ route('teams.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Back to Teams
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Team Units -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-4">Team Roster ({{ $team->units->count() }}/5)</h3>

                            <div class="w-full bg-gray-200 rounded-full h-2 mb-6">
                                <div class="bg-blue-600 h-2 rounded-full"
                                     style="width: {{ ($team->units->count() / 5) * 100 }}%"></div>
                            </div>

                            @if ($team->units->isEmpty())
                                <div class="text-center text-gray-500 py-8">
                                    <div class="text-4xl mb-2">👥</div>
                                    <p>No units in this team yet.</p>
                                    <p class="text-sm">Add units from the panel on the right.</p>
                                </div>
                            @else
                                <div class="space-y-4">
                                    @foreach ($team->units as $unit)
                                        <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors">
                                            <div class="flex justify-between items-start mb-3">
                                                <div>
                                                    <h4 class="font-bold text-lg">{{ $unit->name }}</h4>
                                                    <div class="flex gap-2 mt-1">
                                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold"
                                                              style="background-color: {{ $unit->sector->color }}20; color: {{ $unit->sector->color }};">
                                                            {{ $unit->sector->name }}
                                                        </span>
                                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                                            @if($unit->rarity === 'legendary') bg-yellow-200 text-yellow-900
                                                            @elseif($unit->rarity === 'epic') bg-purple-200 text-purple-900
                                                            @elseif($unit->rarity === 'rare') bg-blue-200 text-blue-900
                                                            @elseif($unit->rarity === 'uncommon') bg-green-200 text-green-900
                                                            @else bg-gray-200 text-gray-900
                                                            @endif">
                                                            {{ ucfirst($unit->rarity) }}
                                                        </span>
                                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-gray-200 text-gray-900">
                                                            Tier {{ $unit->tier }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <form method="POST" action="{{ route('teams.removeUnit', [$team, $unit]) }}" onsubmit="return confirm('Remove this unit from the team?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 text-sm font-semibold">
                                                        Remove
                                                    </button>
                                                </form>
                                            </div>

                                            <div class="grid grid-cols-4 gap-3">
                                                <div class="bg-red-50 p-2 rounded">
                                                    <div class="text-xs text-red-600 font-semibold">HP</div>
                                                    <div class="text-lg font-bold text-red-900">{{ $unit->hp }}</div>
                                                </div>
                                                <div class="bg-orange-50 p-2 rounded">
                                                    <div class="text-xs text-orange-600 font-semibold">ATK</div>
                                                    <div class="text-lg font-bold text-orange-900">{{ $unit->attack }}</div>
                                                </div>
                                                <div class="bg-blue-50 p-2 rounded">
                                                    <div class="text-xs text-blue-600 font-semibold">DEF</div>
                                                    <div class="text-lg font-bold text-blue-900">{{ $unit->defense }}</div>
                                                </div>
                                                <div class="bg-green-50 p-2 rounded">
                                                    <div class="text-xs text-green-600 font-semibold">SPD</div>
                                                    <div class="text-lg font-bold text-green-900">{{ $unit->speed }}</div>
                                                </div>
                                            </div>

                                            @if ($unit->passive_ability)
                                                <div class="mt-2 text-xs text-purple-800 italic bg-purple-50 p-2 rounded">
                                                    {{ $unit->passive_ability }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Add Units Panel -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-4">Available Units</h3>

                            @if ($team->units->count() >= 5)
                                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-4">
                                    Team is full! Remove a unit to add a new one.
                                </div>
                            @endif

                            @if ($availableUnits->isEmpty())
                                <div class="text-center text-gray-500 py-8">
                                    <div class="text-4xl mb-2">📦</div>
                                    <p class="text-sm">No available units.</p>
                                    <p class="text-xs mt-2">All your units are assigned to teams or you need to summon more units.</p>
                                    <a href="{{ route('scan.create') }}"
                                       class="inline-block mt-4 px-3 py-2 bg-gray-800 text-white rounded text-sm hover:bg-gray-700">
                                        Scan to Summon
                                    </a>
                                </div>
                            @else
                                <div class="space-y-3 max-h-96 overflow-y-auto">
                                    @foreach ($availableUnits as $unit)
                                        <div class="border border-gray-200 rounded p-3">
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    <h5 class="font-semibold text-sm">{{ $unit->name }}</h5>
                                                    <div class="flex gap-1 mt-1">
                                                        <span class="inline-block px-1 py-0.5 rounded text-xs font-semibold"
                                                              style="background-color: {{ $unit->sector->color }}20; color: {{ $unit->sector->color }};">
                                                            {{ $unit->sector->name }}
                                                        </span>
                                                        <span class="inline-block px-1 py-0.5 rounded text-xs font-semibold
                                                            @if($unit->rarity === 'legendary') bg-yellow-200 text-yellow-900
                                                            @elseif($unit->rarity === 'epic') bg-purple-200 text-purple-900
                                                            @elseif($unit->rarity === 'rare') bg-blue-200 text-blue-900
                                                            @elseif($unit->rarity === 'uncommon') bg-green-200 text-green-900
                                                            @else bg-gray-200 text-gray-900
                                                            @endif">
                                                            T{{ $unit->tier }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-4 gap-1 mb-2">
                                                <div class="text-center">
                                                    <div class="text-xs text-gray-500">HP</div>
                                                    <div class="text-sm font-bold">{{ $unit->hp }}</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-xs text-gray-500">ATK</div>
                                                    <div class="text-sm font-bold">{{ $unit->attack }}</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-xs text-gray-500">DEF</div>
                                                    <div class="text-sm font-bold">{{ $unit->defense }}</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-xs text-gray-500">SPD</div>
                                                    <div class="text-sm font-bold">{{ $unit->speed }}</div>
                                                </div>
                                            </div>

                                            <form method="POST" action="{{ route('teams.addUnit', $team) }}">
                                                @csrf
                                                <input type="hidden" name="unit_id" value="{{ $unit->id }}">
                                                <button
                                                    type="submit"
                                                    {{ $team->units->count() >= 5 ? 'disabled' : '' }}
                                                    class="w-full px-2 py-1 rounded text-xs font-semibold {{ $team->units->count() >= 5 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-green-600 text-white hover:bg-green-700' }}"
                                                >
                                                    Add to Team
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
