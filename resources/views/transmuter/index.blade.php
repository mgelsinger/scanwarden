<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Essence Transmuter') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            <!-- User's Essence Inventory -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-xl font-bold mb-4">Your Essence</h3>
                @if($userEssence->isEmpty())
                    <p class="text-gray-500 italic">You don't have any essence yet. Scan more items to collect essence!</p>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($userEssence as $essence)
                            <div class="border rounded-lg p-4">
                                <div class="font-semibold">
                                    @if($essence->type === 'generic')
                                        Generic Essence
                                    @else
                                        {{ $essence->sector->name ?? 'Unknown' }} Essence
                                    @endif
                                </div>
                                <div class="text-2xl font-bold text-purple-600 mt-2">
                                    {{ $essence->amount }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Transmutation Recipes -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-bold mb-4">Available Recipes</h3>

                @if($recipes->isEmpty())
                    <p class="text-gray-500 italic">No recipes available at the moment.</p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($recipes as $recipe)
                            <div class="border rounded-lg p-6 {{ $recipe->can_afford ? 'border-green-500' : 'border-gray-300' }}">
                                <div class="flex justify-between items-start mb-3">
                                    <h4 class="text-lg font-bold">{{ $recipe->name }}</h4>
                                    @if($recipe->can_afford)
                                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded font-semibold">
                                            Available
                                        </span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded font-semibold">
                                            Locked
                                        </span>
                                    @endif
                                </div>

                                <p class="text-gray-600 text-sm mb-4">{{ $recipe->description }}</p>

                                <!-- Required Inputs -->
                                <div class="mb-4">
                                    <p class="text-xs font-semibold text-gray-700 mb-2">REQUIRES:</p>
                                    <div class="space-y-1">
                                        @foreach($recipe->required_inputs as $input)
                                            <div class="text-sm text-gray-700">
                                                - {{ $input['amount'] }}
                                                @if($input['type'] === 'essence')
                                                    {{ ucfirst($input['essence_type'] ?? 'generic') }} Essence
                                                @elseif($input['type'] === 'sector_energy')
                                                    {{ \App\Models\Sector::find($input['sector_id'])->name ?? 'Unknown' }} Energy
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Outputs -->
                                <div class="mb-4">
                                    <p class="text-xs font-semibold text-purple-700 mb-2">PRODUCES:</p>
                                    <div class="space-y-1">
                                        @foreach($recipe->outputs as $output)
                                            <div class="text-sm text-purple-700">
                                                -
                                                @if($output['type'] === 'unit_summon')
                                                    {{ ucfirst($output['rarity']) }} {{ \App\Models\Sector::find($output['sector_id'])->name ?? 'Unknown' }} Unit
                                                @elseif($output['type'] === 'essence')
                                                    {{ $output['amount'] }} {{ ucfirst($output['essence_type'] ?? 'generic') }} Essence
                                                @elseif($output['type'] === 'sector_energy')
                                                    {{ $output['amount'] }} {{ \App\Models\Sector::find($output['sector_id'])->name ?? 'Unknown' }} Energy
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Transmute Button -->
                                @if($recipe->can_afford)
                                    <form method="POST" action="{{ route('transmuter.transmute', $recipe) }}"
                                          onsubmit="return confirm('Are you sure you want to perform this transmutation? This action cannot be undone.');">
                                        @csrf
                                        <input type="hidden" name="confirm" value="1">
                                        <button type="submit"
                                                class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded transition-colors">
                                            Transmute
                                        </button>
                                    </form>
                                @else
                                    <button disabled
                                            class="w-full bg-gray-300 text-gray-500 font-bold py-2 px-4 rounded cursor-not-allowed">
                                        Insufficient Resources
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
