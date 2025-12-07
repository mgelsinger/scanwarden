<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Choose Your Starter') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Welcome Message -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Welcome, New Warden!</h3>
                <p class="text-gray-700">
                    Before you begin your journey through the fractured world of ScanWarden, you must choose a starter unit.
                    Each starter represents a different sector and playstyle. Choose wisely - your starter will be your first ally in battles to come.
                </p>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Starter Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($starters as $starter)
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300 border-2 border-gray-200 hover:border-indigo-500">
                        <!-- Header with Sector Color -->
                        <div class="p-4" style="background: linear-gradient(135deg, {{ $starter['sector']->color ?? '#6B7280' }} 0%, {{ $starter['sector']->color ?? '#6B7280' }}99 100%);">
                            <h3 class="text-2xl font-bold text-white mb-1">{{ $starter['name'] }}</h3>
                            <p class="text-white text-sm opacity-90">{{ $starter['sector']->name ?? 'Unknown Sector' }}</p>
                            <span class="inline-block mt-2 px-3 py-1 bg-white bg-opacity-30 rounded-full text-white text-xs font-semibold uppercase">
                                {{ ucfirst($starter['rarity']) }}
                            </span>
                        </div>

                        <!-- Stats -->
                        <div class="p-4 bg-gray-50">
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div class="flex items-center">
                                    <span class="font-semibold text-red-600">HP:</span>
                                    <span class="ml-2">{{ $starter['hp'] }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="font-semibold text-orange-600">ATK:</span>
                                    <span class="ml-2">{{ $starter['attack'] }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="font-semibold text-blue-600">DEF:</span>
                                    <span class="ml-2">{{ $starter['defense'] }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="font-semibold text-green-600">SPD:</span>
                                    <span class="ml-2">{{ $starter['speed'] }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Description and Ability -->
                        <div class="p-4">
                            <p class="text-gray-700 text-sm mb-3">{{ $starter['description'] }}</p>

                            <div class="bg-purple-50 border border-purple-200 rounded p-3 mb-4">
                                <p class="text-xs font-semibold text-purple-900 mb-1">PASSIVE ABILITY</p>
                                <p class="text-sm text-purple-800">{{ $starter['passive_ability'] }}</p>
                            </div>

                            <div class="bg-gray-100 border border-gray-300 rounded p-3 mb-4">
                                <p class="text-xs italic text-gray-600">{{ $starter['lore'] }}</p>
                            </div>
                        </div>

                        <!-- Choose Button -->
                        <div class="p-4 bg-gray-50 border-t">
                            <form method="POST" action="{{ route('starter.store') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="starter_key" value="{{ $starter['key'] }}">
                                <button
                                    type="submit"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-200"
                                    onclick="return confirm('Are you sure you want to choose {{ $starter['name'] }}? This choice is permanent!');"
                                >
                                    Choose {{ $starter['name'] }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
