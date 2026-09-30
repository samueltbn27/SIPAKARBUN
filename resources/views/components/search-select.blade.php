@props([
    'name' => '',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Pilih...',
    'required' => false,
    'model' => null,
    'nameExpression' => null,
    'idExpression' => null,
    'disabledExpression' => null,
])

@php
    $opts = collect($options)->map(function ($option) {
        $value = is_object($option) ? ['value' => $option->value ?? $option->id ?? '', 'name' => $option->name ?? $option->nama ?? $option->label ?? '', 'code' => $option->code ?? $option->kode ?? ''] : (array) $option;
        $name = (string) ($value['name'] ?? $value['nama'] ?? $value['label'] ?? '');
        $code = (string) ($value['code'] ?? $value['kode'] ?? '');
        return [
            'value' => (string) ($value['value'] ?? $value['id'] ?? ''),
            'name' => $name,
            'code' => $code,
            'label' => $code !== '' ? $code . ' — ' . $name : $name,
        ];
    })->values()->all();
    $selectedValue = (string) old($name, $selected);
@endphp

<div
    x-data="{
        open: false,
        query: '',
        selected: @js($selectedValue),
        activeIndex: -1,
        options: @js($opts),
        get fieldName() { return @if($nameExpression) {{ $nameExpression }} @else @js($name) @endif; },
        get blocked() { return ({{ $disabledExpression ?: '[]' }}).map(String); },
        get filtered() {
            const keyword = this.query.trim().toLocaleLowerCase();
            return keyword ? this.options.filter(option => option.label.toLocaleLowerCase().includes(keyword)) : this.options;
        },
        isBlocked(option) { return this.blocked.includes(String(option.value)) && option.value !== this.selected; },
        syncLabel() {
            const found = this.options.find(option => option.value === String(this.selected));
            this.query = found ? found.name : '';
        },
        emit(value, label) {
            window.dispatchEvent(new CustomEvent('search-select-change', { detail: { name: this.fieldName, value, label } }));
        },
        setSelection(value) {
            this.selected = String(value);
            this.$refs.hiddenInput.value = this.selected;
            {{ $model ? $model . ' = String(value);' : '' }}
            this.emit(this.selected, this.options.find(option => option.value === this.selected)?.name || '');
        },
        select(option) {
            if (this.isBlocked(option)) return;
            this.setSelection(option.value);
            this.query = option.name;
            this.open = false;
            this.activeIndex = -1;
            this.$refs.searchInput.setCustomValidity('');
        },
        clear() {
            this.setSelection('');
            this.query = '';
            this.activeIndex = -1;
            this.open = true;
            this.$refs.searchInput.setCustomValidity('');
            this.$refs.searchInput.focus();
        },
        onInput() {
            if (this.selected) this.setSelection('');
            this.open = true;
            this.activeIndex = -1;
            this.$refs.searchInput.setCustomValidity('');
        },
        move(direction) {
            this.open = true;
            const available = this.filtered.map((option, index) => !this.isBlocked(option) ? index : -1).filter(index => index >= 0);
            if (!available.length) return;
            const position = available.indexOf(this.activeIndex);
            this.activeIndex = position < 0 ? (direction > 0 ? available[0] : available[available.length - 1]) : available[(position + direction + available.length) % available.length];
            this.$nextTick(() => this.$refs.results?.querySelector('[data-active=true]')?.scrollIntoView({ block: 'nearest' }));
        },
        chooseActive() {
            if (this.open && this.activeIndex >= 0) this.select(this.filtered[this.activeIndex]);
            else if (!this.open) this.open = true;
        }
    }"
    x-id="['search-select-results']"
    x-init="syncLabel(); $refs.hiddenInput.value = selected"
    @if($model) x-effect="if (selected !== String({{ $model }} || '')) { selected = String({{ $model }} || ''); $refs.hiddenInput.value = selected; syncLabel(); }" @endif
    class="relative min-w-0"
>
    <input x-ref="hiddenInput" type="hidden" @if($nameExpression) :name="{{ $nameExpression }}" @else name="{{ $name }}" @endif :value="selected">
    <div class="relative">
        <input
            x-ref="searchInput"
            type="text"
            @if($idExpression) :id="{{ $idExpression }}" data-symptom-search @else id="{{ $name }}" @endif
            x-model="query"
            @focus="open = true; activeIndex = -1; if (selected) $event.target.select()"
            @click="open = true"
            @input="onInput()"
            @blur="if (query && !selected) $el.setCustomValidity('Pilih salah satu hasil pencarian.'); else $el.setCustomValidity('')"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="chooseActive()"
            @keydown.escape.prevent="open = false; activeIndex = -1"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="open.toString()"
            :aria-controls="$id('search-select-results')"
            :aria-activedescendant="open && activeIndex >= 0 ? $id('search-select-results') + '-' + activeIndex : null"
            autocomplete="off"
            @if($required) required @endif
            placeholder="{{ $placeholder }}"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 pr-11 text-sm text-gray-900 outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200"
        >
        <button type="button" x-show="query" @click="clear()" aria-label="Hapus pilihan" class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 focus-visible:ring-2 focus-visible:ring-green-500" style="display: none;">&times;</button>
    </div>
    <ul
        x-ref="results"
        :id="$id('search-select-results')"
        role="listbox"
        x-show="open"
        @click.outside="open = false"
        class="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
        style="display: none;"
    >
        <template x-for="(option, index) in filtered" :key="option.value">
            <li
                :id="$id('search-select-results') + '-' + index"
                role="option"
                :aria-selected="selected === option.value"
                :aria-disabled="isBlocked(option)"
                :data-active="activeIndex === index"
                @mouseenter="if (!isBlocked(option)) activeIndex = index"
                @mousedown.prevent="select(option)"
                class="cursor-pointer px-3 py-2 text-sm"
                :class="isBlocked(option) ? 'cursor-not-allowed bg-gray-50 text-gray-400' : activeIndex === index || selected === option.value ? 'bg-green-50 text-green-800' : 'text-gray-700 hover:bg-green-50'"
            >
                <span x-show="option.code" class="mr-2 font-mono text-xs" x-text="option.code"></span><span x-text="option.name"></span>
                <span x-show="isBlocked(option)" class="ml-2 text-xs">Sudah dipilih</span>
            </li>
        </template>
        <li x-show="filtered.length === 0" class="px-3 py-3 text-sm text-gray-500">Tidak ada data yang sesuai.</li>
    </ul>
</div>
