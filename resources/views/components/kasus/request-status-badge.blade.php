@props(['status', 'prefix' => null, 'class' => ''])

@php
    $key = (string) $status;
    $labels = config('kasus.request_labels', []);
    $badges = config('kasus.request_status_badge', []);
    $label = $labels[$key] ?? \Illuminate\Support\Str::headline($key);
    $bgClass = $badges[$key]['bg'] ?? 'bg-[#eef3ef] text-[#66746c]';
    $iconPath = $badges[$key]['icon'] ?? null;
    $prefixText = $prefix !== null ? $prefix.' ' : '';
@endphp

<span {{ $attributes->merge([
    'class' => trim("inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {$bgClass} {$class}"),
]) }}>
    @if ($iconPath)
        <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="{{ $iconPath }}" />
        </svg>
    @endif
    {{ $prefixText }}{{ $label }}
</span>
