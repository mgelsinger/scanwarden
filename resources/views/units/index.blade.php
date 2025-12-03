<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Units') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($units->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">
                        <p class="text-lg mb-4">No units yet. Scan UPCs to summon your first unit!</p>
                        <a href="{{ route('scan.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Start Scanning
                        </a>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Total Units: {{ $units->count() }}</h3>
                            <a href="{{ route('scan.create') }}" class="text-sm text-blue-600 hover:text-blue-800">
                                Scan for More →
                            </a>
                        </div>

                        <form method="GET" class="flex gap-4 mb-6">
                            <select name="sector" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Sectors</option>
                                @foreach(\App\Models\Sector::all() as $sector)
                                    <option value="{{ $sector->id }}" {{ $filters['sector'] == $sector->id ? 'selected' : '' }}>
                                        {{ $sector->name }}
                                    </option>
                                @endforeach
                            </select>

                            <select name="rarity" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Rarities</option>
                                <option value="common" {{ $filters['rarity'] == 'common' ? 'selected' : '' }}>Common</option>
                                <option value="uncommon" {{ $filters['rarity'] == 'uncommon' ? 'selected' : '' }}>Uncommon</option>
                                <option value="rare" {{ $filters['rarity'] == 'rare' ? 'selected' : '' }}>Rare</option>
                                <option value="epic" {{ $filters['rarity'] == 'epic' ? 'selected' : '' }}>Epic</option>
                                <option value="legendary" {{ $filters['rarity'] == 'legendary' ? 'selected' : '' }}>Legendary</option>
                            </select>

                            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
                                Filter
                            </button>
                            <a href="{{ route('units.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                Clear
                            </a>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($units as $unit)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-3">
                                    <h3 class="text-xl font-bold">{{ $unit->name }}</h3>
                                    @if ($unit->can_evolve)
                                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded font-semibold">
                                            Can Evolve!
                                        </span>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold mr-2"
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
                                </div>

                                <div class="text-sm text-gray-600 mb-3">
                                    Tier {{ $unit->tier }}
                                </div>

                                <div class="grid grid-cols-2 gap-2 mb-4 text-sm">
                                    <div class="bg-gray-50 p-2 rounded">
                                        <div class="text-xs text-gray-500">HP</div>
                                        <div class="font-bold">{{ $unit->hp }}</div>
                                    </div>
                                    <div class="bg-gray-50 p-2 rounded">
                                        <div class="text-xs text-gray-500">ATK</div>
                                        <div class="font-bold">{{ $unit->attack }}</div>
                                    </div>
                                    <div class="bg-gray-50 p-2 rounded">
                                        <div class="text-xs text-gray-500">DEF</div>
                                        <div class="font-bold">{{ $unit->defense }}</div>
                                    </div>
                                    <div class="bg-gray-50 p-2 rounded">
                                        <div class="text-xs text-gray-500">SPD</div>
                                        <div class="font-bold">{{ $unit->speed }}</div>
                                    </div>
                                </div>

                                <a href="{{ route('units.show', $unit) }}"
                                   class="block text-center px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 text-sm font-semibold">
                                    View Details
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
