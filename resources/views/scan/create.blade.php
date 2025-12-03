<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Scan UPC') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-2xl font-bold mb-4">Scan a UPC to Summon Units</h3>

                    <p class="text-gray-600 mb-6">
                        Enter a Universal Product Code (UPC) from any real-world product to discover its sector affinity
                        and potentially summon a new unit to your collection.
                    </p>

                    @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('scan.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="upc" class="block font-medium text-sm text-gray-700">
                                UPC Code
                            </label>
                            <input
                                id="upc"
                                name="upc"
                                type="text"
                                value="{{ old('upc') }}"
                                placeholder="Example: 012345678905"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                                autofocus
                                maxlength="20"
                                pattern="[0-9]+"
                                title="Please enter only numbers"
                            />
                            <p class="mt-2 text-sm text-gray-500">
                                Enter 8-20 digits. You can find UPC codes on product packaging.
                            </p>
                        </div>

                        <div class="flex items-center justify-between">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Scan UPC
                            </button>
                        </div>
                    </form>

                    <div class="mt-8 border-t pt-6">
                        <h4 class="font-bold mb-2">Example UPCs:</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm text-gray-600">
                            <div>012345678905 - Random Sector</div>
                            <div>042100005264 - Likely Food</div>
                            <div>790572453903 - Likely Tech</div>
                            <div>300450147202 - Likely Bio</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
