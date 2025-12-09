<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Scan Results') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-2xl font-bold mb-4">Scan Complete!</h3>

                    <div class="space-y-4">
                        <div>
                            <span class="font-semibold">UPC Scanned:</span>
                            <span class="font-mono">{{ $scanRecord->raw_upc }}</span>
                        </div>

                        <div class="p-4 rounded-lg" style="background-color: {{ $sector->color }}20; border-left: 4px solid {{ $sector->color }};">
                            <h4 class="text-xl font-bold mb-2" style="color: {{ $sector->color }};">
                                {{ $sector->name }}
                            </h4>
                            <p class="text-gray-700">{{ $sector->description }}</p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold mb-2">Rewards:</h4>
                            <ul class="list-disc list-inside space-y-1">
                                <li>
                                    <span class="font-semibold">Energy Gained:</span>
                                    {{ $rewards['energy_gained'] }} {{ $sector->name }} Energy
                                </li>

                                @if ($rewards['should_summon'] && $rewards['summoned_unit'])
                                    <li class="text-green-600 font-bold">
                                        New Unit Summoned!
                                    </li>
                                @else
                                    <li class="text-gray-600">
                                        No unit summoned this time. Keep scanning!
                                    </li>
                                @endif

                                @if (!empty($rewards['essence_rewards']))
                                    @foreach ($rewards['essence_rewards'] as $essenceReward)
                                        <li class="text-purple-600 font-semibold">
                                            +{{ $essenceReward['amount'] }}
                                            @if ($essenceReward['type'] === 'generic')
                                                Generic Essence
                                            @elseif ($essenceReward['type'] === 'sector')
                                                {{ $essenceReward['sector_name'] ?? 'Sector' }} Essence
                                            @elseif ($essenceReward['type'] === 'summon_bonus')
                                                Bonus Essence (Summon!)
                                            @endif
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>

                        @if ($rewards['should_summon'] && $rewards['summoned_unit'])
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-500 p-6 rounded-lg">
                                <h4 class="text-2xl font-bold mb-4 text-green-800">
                                    {{ $rewards['summoned_unit']['name'] }}
                                </h4>

                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="text-sm text-gray-600">Rarity:</span>
                                        <span class="ml-2 px-2 py-1 rounded font-semibold text-sm
                                            @if($rewards['summoned_unit']['rarity'] === 'legendary') bg-yellow-200 text-yellow-900
                                            @elseif($rewards['summoned_unit']['rarity'] === 'epic') bg-purple-200 text-purple-900
                                            @elseif($rewards['summoned_unit']['rarity'] === 'rare') bg-blue-200 text-blue-900
                                            @elseif($rewards['summoned_unit']['rarity'] === 'uncommon') bg-green-200 text-green-900
                                            @else bg-gray-200 text-gray-900
                                            @endif">
                                            {{ ucfirst($rewards['summoned_unit']['rarity']) }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-600">Tier:</span>
                                        <span class="ml-2 font-bold">{{ $rewards['summoned_unit']['tier'] }}</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                                    <div class="bg-white p-3 rounded">
                                        <div class="text-xs text-gray-500">HP</div>
                                        <div class="text-lg font-bold">{{ $rewards['summoned_unit']['stats']['hp'] }}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded">
                                        <div class="text-xs text-gray-500">Attack</div>
                                        <div class="text-lg font-bold">{{ $rewards['summoned_unit']['stats']['attack'] }}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded">
                                        <div class="text-xs text-gray-500">Defense</div>
                                        <div class="text-lg font-bold">{{ $rewards['summoned_unit']['stats']['defense'] }}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded">
                                        <div class="text-xs text-gray-500">Speed</div>
                                        <div class="text-lg font-bold">{{ $rewards['summoned_unit']['stats']['speed'] }}</div>
                                    </div>
                                </div>

                                @if ($rewards['summoned_unit']['passive_ability'])
                                    <div class="bg-white p-3 rounded">
                                        <div class="text-xs text-gray-500 font-semibold mb-1">Passive Ability</div>
                                        <div class="text-sm italic">{{ $rewards['summoned_unit']['passive_ability'] }}</div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 flex gap-4">
                        <a
                            href="{{ route('scan.create') }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Scan Another UPC
                        </a>
                        <a
                            href="{{ route('dashboard') }}"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
