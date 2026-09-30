@php
    $record = $penyakit ?? null;
    $fieldClass = 'mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200';
    $checkedCommodities = array_map('strval', old('komoditas_id', $selectedKomoditas ?? []));
@endphp

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <div class="mb-5">
        <h2 class="text-base font-bold text-[#173b29]">Informasi Penyakit</h2>
        <p class="mt-1 text-sm text-gray-500">Nama dan deskripsi yang jelas membantu pengelolaan basis pengetahuan.</p>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="nama" class="block text-sm font-medium text-gray-700">Nama Penyakit <span class="text-red-500">*</span></label>
            <input type="text" id="nama" name="nama" value="{{ old('nama', $record?->nama) }}" required aria-describedby="nama-error" class="{{ $fieldClass }}">
            @error('nama')<p id="nama-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="kode" class="block text-sm font-medium text-gray-700">Kode Penyakit <span class="text-gray-400">(opsional)</span></label>
            <input type="text" id="kode" name="kode" value="{{ old('kode', $record?->kode) }}" class="{{ $fieldClass }}">
            @error('kode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi Penyakit</label>
            <textarea id="deskripsi" name="deskripsi" rows="5" class="{{ $fieldClass }}">{{ old('deskripsi', $record?->deskripsi) }}</textarea>
            @error('deskripsi')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</section>

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6" x-data="{ search: '', names: @js($komoditas->map(fn ($item) => mb_strtolower($item->nama . ' ' . $item->kode))->values()), get hasMatches() { return this.names.some(item => item.includes(this.search.trim().toLocaleLowerCase())); } }">
    <h2 class="text-base font-bold text-[#173b29]">Komoditas Terkait</h2>
    <p class="mt-1 text-sm text-gray-500">Pilih komoditas terverifikasi yang dapat terkena penyakit ini. Lebih dari satu pilihan diperbolehkan.</p>
    @if ($komoditas->isNotEmpty())
        <label for="cari-komoditas" class="mt-5 block text-sm font-medium text-gray-700">Cari Komoditas</label>
        <input type="search" id="cari-komoditas" x-model="search" placeholder="Cari nama atau kode komoditas..." class="{{ $fieldClass }}">
        <div class="mt-4 grid max-h-72 gap-2 overflow-y-auto pr-1 sm:grid-cols-2" role="group" aria-label="Komoditas terkait">
            @foreach ($komoditas as $item)
                <label x-show="@js(mb_strtolower($item->nama . ' ' . $item->kode)).includes(search.trim().toLocaleLowerCase())" class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 px-3 py-3 text-sm text-gray-700 hover:bg-gray-50">
                    <input type="checkbox" name="komoditas_id[]" value="{{ $item->id }}" @checked(in_array((string) $item->id, $checkedCommodities)) class="mt-0.5 rounded border-gray-300 text-green-600 focus:ring-green-200">
                    <span><span class="block font-medium">{{ $item->nama }}</span><span class="font-mono text-xs text-gray-500">{{ $item->kode }}</span></span>
                </label>
            @endforeach
        </div>
        <p x-show="search && !hasMatches" class="mt-2 text-sm text-gray-500">Tidak ada data yang sesuai.</p>
    @else
        <p class="mt-4 text-sm text-gray-500">Belum ada komoditas terverifikasi yang terdaftar.</p>
    @endif
    @error('komoditas_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    @error('komoditas_id.*')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
</section>

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <h2 class="text-base font-bold text-[#173b29]">Media dan Status</h2>
    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <div>
            <label for="image" class="block text-sm font-medium text-gray-700">Foto Penyakit <span class="text-gray-400">(opsional)</span></label>
            @if ($record?->image_path)<p class="mt-1 text-xs text-gray-500">Foto saat ini tersedia. Pilih file baru untuk menggantinya.</p>@endif
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" class="{{ $fieldClass }}">
            <p class="mt-1 text-xs text-gray-500">JPG, PNG, atau WebP; maksimal 5 MB.</p>
            @error('image')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <x-knowledge.status-select name="status" :value="$record?->status" default="draft" :locked="auth()->user()?->hasRole('popt') ?? false" />
    </div>
</section>
