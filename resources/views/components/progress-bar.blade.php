@props([
    'value' => 0,
    'max' => 100,
])

@php
    $max = max(1, (int) $max);
    $value = max(0, (int) $value);
    $pct = min(100, (int) round(($value / $max) * 100));
@endphp

<div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
    <div class="h-full bg-indigo-500" style="width: {{ $pct }}%;"></div>
</div>
