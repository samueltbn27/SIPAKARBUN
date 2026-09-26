@props([
    'name' => '',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Pilih...',
    'required' => false,
])

@php
    $opts = collect($options)->map(function ($o) {
        if (is_object($o)) {
            return [
                'value' => (string) ($o->value ?? $o->id ?? ''),
                'label' => (string) ($o->label ?? $o->nama ?? ''),
            ];
        }
        $o = (array) $o;
        return [
            'value' => (string) ($o['value'] ?? $o['id'] ?? ''),
            'label' => (string) ($o['label'] ?? $o['nama'] ?? ''),
        ];
    })->values()->all();
    $selectedVal = (string) old($name, $selected);
@endphp

<div
    x-data="{
        open: false,
        query: '',
        selected: '{{ $selectedVal }}',
        options: @js($opts),
        get filtered() {
            const q = this.query.trim().toLowerCase();
            return q ? this.options.filter(o => o.label.toLowerCase().includes(q)) : this.options;
        },
        select(o) { this.selected = o.value; this.query = o.label; this.open = false; },
        clear() { this.selected = ''; this.query = ''; }
    }"
    x-init="
        const found = options.find(o => o.value === selected);
        if (found) query = found.label;
    "
    class="relative"
>
    <input type="hidden" name="{{ $name }}" x-model="selected" @if($required) required @endif>

    <div class="relative">
        <input
            type="text"
            x-model="query"
            @focus="open = true; $event.target.select()"
            @click="open = true"
            @input="open = true"
            @keydown.escape="open = false"
            placeholder="{{ $placeholder }}"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-8 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none"
        >
        <button type="button" x-show="query" @click="clear()" tabindex="-1"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">✕</button>
        <svg x-show="!open" class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
    </div>

    <ul
        x-show="open"
        @click.outside="open = false"
        x-transition
        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg"
        style="display: none;"
    >
        <template x-for="opt in filtered" :key="opt.value">
            <li @click="select(opt)"
                class="cursor-pointer px-3 py-2 text-sm hover:bg-green-50"
                :class="selected === opt.value ? 'bg-green-50 font-medium text-green-700' : 'text-gray-700'"
                x-text="opt.label"></li>
        </template>
        <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">Tidak ada hasil</li>
    </ul>
</div>
