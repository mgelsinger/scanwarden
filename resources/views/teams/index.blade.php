<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Teams') }}
            </h2>
            <a href="{{ route('teams.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Create New Team
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if ($teams->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">
                        <div class="text-6xl mb-4">⚔️</div>
                        <h3 class="text-xl font-semibold mb-2">No Teams Yet</h3>
                        <p class="mb-4">Create your first team to start battling!</p>
                        <a href="{{ route('teams.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Create Your First Team
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($teams as $team)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-800">{{ $team->name }}</h3>
                                        <p class="text-sm text-gray-500">
                                            {{ $team->units_count }} / 5 units
                                        </p>
                                    </div>
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-xl">
                                        {{ $team->units_count }}
                                    </div>
                                </div>

                                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                                    <div class="bg-blue-600 h-2 rounded-full"
                                         style="width: {{ ($team->units_count / 5) * 100 }}%"></div>
                                </div>

                                <div class="flex gap-2">
                                    <a href="{{ route('teams.show', $team) }}"
                                       class="flex-1 text-center px-3 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 text-sm font-semibold">
                                        View Details
                                    </a>
                                    <a href="{{ route('teams.edit', $team) }}"
                                       class="px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-semibold">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
