@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'soft-card rounded-2xl border border-[#e4ece7] bg-white '.$class]) }}>
    {{ $slot }}
</div>
