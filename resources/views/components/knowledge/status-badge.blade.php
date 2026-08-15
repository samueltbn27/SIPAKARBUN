@props(['status'])
@php
    $styles = [
        'aktif' => ['bg' => '#e8f4ed', 'text' => '#176b45', 'label' => 'Aktif'],
        'draft' => ['bg' => '#fff4df', 'text' => '#b8860b', 'label' => 'Draft'],
        'nonaktif' => ['bg' => '#f0f4f1', 'text' => '#8b9790', 'label' => 'Nonaktif'],
    ];
    $cfg = $styles[$status] ?? $styles['nonaktif'];
@endphp
<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold" style="background:{{ $cfg['bg'] }};color:{{ $cfg['text'] }}">
    <span class="w-1.5 h-1.5 rounded-full" style="background:{{ $cfg['text'] }}"></span>
    {{ $cfg['label'] }}
</span>
