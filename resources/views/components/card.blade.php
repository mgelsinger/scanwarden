@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'bg-gray-900/70 border border-gray-800 rounded-xl p-4 shadow-sm']) }}>
    @if($title)
        <div class="mb-2">
            <h2 class="text-sm font-semibold text-gray-100">
                {{ $title }}
            </h2>
            @if($subtitle)
                <p class="text-xs text-gray-400 mt-1">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
