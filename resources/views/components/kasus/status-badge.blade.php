@props(['status'])

@php($label = config('kasus.labels.'.$status, (string) $status))
<span {{ $attributes->merge(['class' => 'rounded-full bg-[#eef6f1] px-2.5 py-1 text-xs font-semibold text-[#176b45]']) }}>{{ $label }}</span>
