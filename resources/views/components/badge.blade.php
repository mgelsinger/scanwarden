@props([
    'variant' => 'default',
])

@php
    $base = 'inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold';
    $variants = [
        'default' => 'bg-gray-700 text-gray-100',
        'success' => 'bg-emerald-700/60 text-emerald-200',
        'warning' => 'bg-yellow-700/60 text-yellow-200',
        'danger'  => 'bg-red-700/60 text-red-200',
        'info'    => 'bg-indigo-700/60 text-indigo-200',
    ];
    $classes = $base . ' ' . ($variants[$variant] ?? $variants['default']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
