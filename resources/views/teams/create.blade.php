<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New Team') }}
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('teams.store') }}">
                        @csrf

                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Team Name
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                maxlength="255"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter a name for your team"
                            >
                            <p class="mt-1 text-sm text-gray-500">
                                Choose a memorable name for your team (max 255 characters)
                            </p>
                        </div>

                        <div class="flex gap-4">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                            >
                                Create Team
                            </button>
                            <a
                                href="{{ route('teams.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                            >
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Team Tips</h3>
                <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                    <li>Each team can have up to 5 units</li>
                    <li>Units can only be in one team at a time</li>
                    <li>You can add units after creating the team</li>
                    <li>Higher tier and rarity units make stronger teams</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
