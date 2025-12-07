<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $unit->name }}
        </h2>
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
                <!-- Unit Info Card -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <h1 class="text-3xl font-bold mb-2">{{ $unit->name }}</h1>
                                    <div class="flex gap-2">
                                        <span class="inline-block px-3 py-1 rounded text-sm font-semibold"
                                            style="background-color: {{ $unit->sector->color }}20; color: {{ $unit->sector->color }};">
                                            {{ $unit->sector->name }}
                                        </span>
                                        <span class="inline-block px-3 py-1 rounded text-sm font-semibold text-white"
                                            style="background-color: {{ config('rarities.tiers.' . $unit->rarity . '.color') }};">
                                            {{ config('rarities.tiers.' . $unit->rarity . '.name') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-4xl font-bold text-gray-800">{{ $unit->tier }}</div>
                                    <div class="text-sm text-gray-500">Tier</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <div class="bg-gradient-to-br from-red-50 to-red-100 p-4 rounded-lg border border-red-200">
                                    <div class="text-xs text-red-600 font-semibold mb-1">HP</div>
                                    <div class="text-2xl font-bold text-red-900">{{ $unit->hp }}</div>
                                </div>
                                <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-4 rounded-lg border border-orange-200">
                                    <div class="text-xs text-orange-600 font-semibold mb-1">Attack</div>
                                    <div class="text-2xl font-bold text-orange-900">{{ $unit->attack }}</div>
                                </div>
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                                    <div class="text-xs text-blue-600 font-semibold mb-1">Defense</div>
                                    <div class="text-2xl font-bold text-blue-900">{{ $unit->defense }}</div>
                                </div>
                                <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                                    <div class="text-xs text-green-600 font-semibold mb-1">Speed</div>
                                    <div class="text-2xl font-bold text-green-900">{{ $unit->speed }}</div>
                                </div>
                            </div>

                            @if ($unit->passive_ability)
                                <div class="bg-purple-50 border border-purple-200 p-4 rounded-lg">
                                    <div class="font-semibold text-purple-900 mb-2">Passive Ability</div>
                                    <div class="text-purple-800 italic">{{ $unit->passive_ability }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Evolution Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-4">Evolution</h3>

                            @if ($requirements)
                                <div class="mb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-600">Current Tier</span>
                                        <span class="font-bold">{{ $unit->tier }}</span>
                                    </div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-600">Next Tier</span>
                                        <span class="font-bold text-green-600">{{ $requirements['to_tier'] }}</span>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                                    <div class="text-sm font-semibold mb-2">Required Energy</div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm">You have:</span>
                                        <span class="font-bold {{ $userSectorEnergy >= $requirements['required_energy'] ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $userSectorEnergy }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm">Required:</span>
                                        <span class="font-bold">{{ $requirements['required_energy'] }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ min(($userSectorEnergy / $requirements['required_energy']) * 100, 100) }}%"></div>
                                    </div>
                                </div>

                                @if ($preview)
                                    <div class="border-t pt-4 mb-4">
                                        <h4 class="font-semibold mb-2 text-sm">Stat Changes</h4>
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span>HP:</span>
                                                <span class="text-green-600">{{ $preview['current_stats']['hp'] }} → {{ $preview['new_stats']['hp'] }} (+{{ $preview['stat_gains']['hp'] }})</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span>Attack:</span>
                                                <span class="text-green-600">{{ $preview['current_stats']['attack'] }} → {{ $preview['new_stats']['attack'] }} (+{{ $preview['stat_gains']['attack'] }})</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span>Defense:</span>
                                                <span class="text-green-600">{{ $preview['current_stats']['defense'] }} → {{ $preview['new_stats']['defense'] }} (+{{ $preview['stat_gains']['defense'] }})</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span>Speed:</span>
                                                <span class="text-green-600">{{ $preview['current_stats']['speed'] }} → {{ $preview['new_stats']['speed'] }} (+{{ $preview['stat_gains']['speed'] }})</span>
                                            </div>
                                        </div>

                                        @if ($preview['new_trait'] && !$unit->passive_ability)
                                            <div class="mt-3 p-2 bg-purple-50 border border-purple-200 rounded">
                                                <div class="text-xs font-semibold text-purple-900">New Trait</div>
                                                <div class="text-xs text-purple-800 italic">{{ $preview['new_trait'] }}</div>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('units.evolve', $unit) }}">
                                    @csrf
                                    <button type="submit"
                                            {{ $canEvolve ? '' : 'disabled' }}
                                            class="w-full px-4 py-3 rounded-md font-semibold text-white {{ $canEvolve ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed' }}">
                                        @if ($canEvolve)
                                            Evolve Unit
                                        @else
                                            Not Enough Energy
                                        @endif
                                    </button>
                                </form>
                            @else
                                <div class="text-center text-gray-500">
                                    <p class="mb-2">Max tier reached!</p>
                                    <p class="text-sm">This unit cannot evolve further.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('units.index') }}"
                           class="block text-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Back to Units
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
