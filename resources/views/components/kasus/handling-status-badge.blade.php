@props(['status' => null, 'class' => ''])

@if ($status === null)
    <span class="text-xs text-[#8a9990]">Belum tersedia</span>
@else
    @php
        $label = $status['label'] ?? 'Belum tersedia';
        $bgClass = $status['badge_class'] ?? 'bg-gray-100 text-gray-600';
        $iconPath = $status['icon_path'] ?? null;
    @endphp
    <span {{ $attributes->merge([
        'class' => trim("inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {$bgClass} {$class}"),
    ]) }}>
        @if ($iconPath)
            <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="{{ $iconPath }}" />
            </svg>
        @endif
        {{ $label }}
    </span>
@endif
