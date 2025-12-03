<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $lore->title }}
            </h2>
            <a href="{{ route('lore.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                Back to Lore
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8">
                    <!-- Header -->
                    <div class="mb-6">
                        <h1 class="text-4xl font-bold mb-3">{{ $lore->title }}</h1>
                        <div class="flex gap-2">
                            @if ($lore->sector)
                                <span class="inline-block px-3 py-1 rounded font-semibold"
                                      style="background-color: {{ $lore->sector->color }}20; color: {{ $lore->sector->color }};">
                                    {{ $lore->sector->name }}
                                </span>
                            @else
                                <span class="inline-block px-3 py-1 rounded font-semibold bg-gray-200 text-gray-800">
                                    Universal Lore
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="prose max-w-none">
                        <p class="text-lg leading-relaxed text-gray-800 whitespace-pre-wrap">{{ $lore->body }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
