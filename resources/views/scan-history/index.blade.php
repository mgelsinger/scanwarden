<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Scan History') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filter Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Filter Scans</h3>
                    <form method="GET" action="{{ route('scan-history.index') }}" class="flex gap-4">
                        <select name="sector" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Sectors</option>
                            @foreach($sectors as $sector)
                                <option value="{{ $sector->id }}" {{ $selectedSector == $sector->id ? 'selected' : '' }}>
                                    {{ $sector->name }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
                            Apply Filter
                        </button>

                        @if($selectedSector)
                            <a href="{{ route('scan-history.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                Clear Filter
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Scan History Table -->
            @if ($scans->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">
                        <div class="text-6xl mb-4">🔍</div>
                        <h3 class="text-xl font-semibold mb-2">No Scans Yet</h3>
                        <p class="mb-4">Start scanning UPCs to build your unit collection!</p>
                        <a href="{{ route('scan.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Scan Now
                        </a>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date & Time
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        UPC Code
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Sector
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Rewards
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($scans as $scan)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $scan->created_at->format('M d, Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ $scan->created_at->format('h:i A') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-mono text-gray-900">{{ $scan->raw_upc }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-block px-3 py-1 rounded text-sm font-semibold"
                                                  style="background-color: {{ $scan->sector->color }}20; color: {{ $scan->sector->color }};">
                                                {{ $scan->sector->name }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                @if($scan->rewards['unit_summoned'] ?? $scan->rewards['should_summon'] ?? false)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        ✓ Unit Summoned
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                +{{ $scan->rewards['energy'] ?? $scan->rewards['energy_gained'] ?? 0 }} Energy
                                            </div>
                                            @if (!empty($scan->rewards['essence_rewards']))
                                                <div class="text-xs text-purple-600 mt-1">
                                                    @php
                                                        $totalEssence = array_sum(array_column($scan->rewards['essence_rewards'], 'amount'));
                                                    @endphp
                                                    +{{ $totalEssence }} Essence
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('scan.result', $scan) }}" class="text-indigo-600 hover:text-indigo-900">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $scans->links() }}
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-sm font-medium text-gray-500">Total Scans</div>
                            <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $scans->total() }}</div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-sm font-medium text-gray-500">Units Summoned</div>
                            <div class="mt-1 text-3xl font-semibold text-green-600">
                                {{ $scans->filter(fn($scan) => $scan->rewards['unit_summoned'] ?? false)->count() }}
                            </div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-sm font-medium text-gray-500">Total Energy Gained</div>
                            <div class="mt-1 text-3xl font-semibold text-blue-600">
                                {{ $scans->sum(fn($scan) => $scan->rewards['energy'] ?? 0) }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
