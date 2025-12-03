<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Lore Entries') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Sector Filter -->
            <div class="mb-6 bg-white rounded-lg shadow p-4">
                <h3 class="font-semibold mb-3">Filter by Sector</h3>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('lore.index') }}"
                       class="px-4 py-2 rounded {{ !$sectorFilter ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                        All
                    </a>
                    @foreach ($sectors as $sector)
                        <a href="{{ route('lore.index', ['sector' => $sector->id]) }}"
                           class="px-4 py-2 rounded {{ $sectorFilter == $sector->id ? 'text-white' : 'hover:opacity-80' }}"
                           style="background-color: {{ $sectorFilter == $sector->id ? $sector->color : $sector->color . '40' }};">
                            {{ $sector->name }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Unlocked Lore -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-2xl font-bold mb-4">Unlocked Lore</h3>
                    @if ($unlockedLore->isEmpty())
                        <div class="text-center text-gray-500 py-8">
                            <div class="text-4xl mb-2">📖</div>
                            <p>No lore unlocked yet. Complete milestones to discover the secrets!</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($unlockedLore as $lore)
                                <a href="{{ route('lore.show', $lore) }}"
                                   class="border border-gray-200 rounded-lg p-4 hover:border-gray-400 hover:shadow-md transition-all">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="text-lg font-bold">{{ $lore->title }}</h4>
                                        @if ($lore->sector)
                                            <span class="text-xs px-2 py-1 rounded font-semibold"
                                                  style="background-color: {{ $lore->sector->color }}20; color: {{ $lore->sector->color }};">
                                                {{ $lore->sector->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600 line-clamp-2">
                                        {{ Str::limit($lore->body, 120) }}
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Locked Lore -->
            @if ($lockedLore->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-2xl font-bold mb-4">Locked Lore</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($lockedLore as $lore)
                                <div class="border border-gray-300 rounded-lg p-4 bg-gray-50 opacity-75">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="text-lg font-bold text-gray-600">{{ $lore->title }}</h4>
                                        @if ($lore->sector)
                                            <span class="text-xs px-2 py-1 rounded font-semibold"
                                                  style="background-color: {{ $lore->sector->color }}20; color: {{ $lore->sector->color }};">
                                                {{ $lore->sector->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500 mb-3">
                                        🔒 Unlock requirement: {{ ucwords(str_replace('_', ' ', $lore->unlock_key)) }}
                                        ({{ $lore->progress['current'] }}/{{ $lore->progress['required'] }})
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $lore->progress['progress'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
