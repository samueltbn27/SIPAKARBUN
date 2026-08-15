@props(['name' => 'status', 'value' => null, 'default' => 'draft', 'label' => 'Status'])
<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
    <select id="{{ $name }}" name="{{ $name }}"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none bg-white">
        <option value="draft" @selected(old($name, $value ?? $default) === 'draft')>Draft (belum dipakai diagnosis)</option>
        <option value="aktif" @selected(old($name, $value ?? $default) === 'aktif')>Aktif (terpublikasi)</option>
        <option value="nonaktif" @selected(old($name, $value ?? $default) === 'nonaktif')>Nonaktif</option>
    </select>
    @if ($errors->has($name))
        <p class="mt-1 text-xs text-red-600">{{ $errors->first($name) }}</p>
    @endif
</div>
