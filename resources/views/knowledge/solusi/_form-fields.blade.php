@php
    $record = $solusi ?? null;
    $fieldClass = 'mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200';
@endphp

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <h2 class="text-base font-bold text-[#173b29]">Target Penyakit</h2>
    <p class="mt-1 text-sm text-gray-500">Cari penyakit yang akan menerima rekomendasi ini.</p>
    <div class="mt-5">
        <label for="penyakit_id" class="block text-sm font-medium text-gray-700">Penyakit <span class="text-red-500">*</span></label>
        <div class="mt-1"><x-search-select name="penyakit_id" :options="$penyakitList" :selected="old('penyakit_id', $record?->penyakit_id)" placeholder="Cari nama atau kode penyakit..." required /></div>
        @error('penyakit_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</section>

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <h2 class="text-base font-bold text-[#173b29]">Rekomendasi Penanganan</h2>
    <div class="mt-5 space-y-5">
        <div>
            <label for="judul" class="block text-sm font-medium text-gray-700">Judul Solusi <span class="text-red-500">*</span></label>
            <input type="text" name="judul" id="judul" value="{{ old('judul', $record?->judul) }}" required class="{{ $fieldClass }}">
            @error('judul')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi Solusi <span class="text-red-500">*</span></label>
            <textarea name="deskripsi" id="deskripsi" rows="7" required class="{{ $fieldClass }}">{{ old('deskripsi', $record?->deskripsi) }}</textarea>
            @error('deskripsi')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</section>

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <h2 class="mb-4 text-base font-bold text-[#173b29]">Status Knowledge</h2>
    <x-knowledge.status-select name="status" :value="$record?->status" default="draft" :locked="auth()->user()?->hasRole('popt') ?? false" />
</section>
