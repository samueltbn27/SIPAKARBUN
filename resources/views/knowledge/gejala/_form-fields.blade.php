@php
    $record = $gejala ?? null;
    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200';
@endphp

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <div class="mb-5">
        <h2 class="text-base font-bold text-[#173b29]">Informasi Gejala</h2>
        <p class="mt-1 text-sm text-gray-500">Jelaskan gejala dengan istilah yang objektif dan mudah dikenali.</p>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="kode" class="mb-1 block text-sm font-medium text-gray-700">Kode Gejala <span class="text-gray-400">(opsional)</span></label>
            <input type="text" name="kode" id="kode" value="{{ old('kode', $record?->kode) }}" placeholder="Contoh: G01" class="{{ $fieldClass }}">
            @error('kode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="nama" class="mb-1 block text-sm font-medium text-gray-700">Nama Gejala <span class="text-red-500">*</span></label>
            <input type="text" name="nama" id="nama" value="{{ old('nama', $record?->nama) }}" required class="{{ $fieldClass }}">
            @error('nama')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label for="deskripsi" class="mb-1 block text-sm font-medium text-gray-700">Deskripsi</label>
            <textarea name="deskripsi" id="deskripsi" rows="5" class="{{ $fieldClass }}">{{ old('deskripsi', $record?->deskripsi) }}</textarea>
            @error('deskripsi')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</section>

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <div class="mb-5">
        <h2 class="text-base font-bold text-[#173b29]">Kriteria Observasi</h2>
        <p class="mt-1 text-sm text-gray-500">Tuliskan warna, bentuk, lokasi, jumlah, ukuran, atau karakter lain sesuai sumber. Tingkat keparahan gejala bukan nilai CF.</p>
    </div>
    <div class="grid gap-5">
        <div>
            <label for="kriteria_observasi" class="mb-1 block text-sm font-medium text-gray-700">Kriteria Observasi</label>
            <textarea name="kriteria_observasi" id="kriteria_observasi" rows="5" placeholder="Contoh: bercak berwarna kuning, berbentuk tidak beraturan, terlihat pada permukaan bawah daun." class="{{ $fieldClass }}">{{ old('kriteria_observasi', $record?->kriteria_observasi) }}</textarea>
            <p class="mt-1 text-xs text-gray-500">Dapat berupa ada/tidak ada, skala, persentase area, jumlah, ukuran lesi, atau karakter visual sesuai referensi.</p>
            @error('kriteria_observasi')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="metode_pengamatan" class="mb-1 block text-sm font-medium text-gray-700">Metode Pengamatan</label>
            <input type="text" name="metode_pengamatan" id="metode_pengamatan" maxlength="150" value="{{ old('metode_pengamatan', $record?->metode_pengamatan) }}" placeholder="Contoh: Observasi visual pada permukaan atas dan bawah daun" class="{{ $fieldClass }}">
            @error('metode_pengamatan')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</section>

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <div class="mb-5">
        <h2 class="text-base font-bold text-[#173b29]">Referensi</h2>
        <p class="mt-1 text-sm text-gray-500">Kosongkan jika belum ada sumber yang dapat diverifikasi. Jangan mencantumkan referensi perkiraan.</p>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="referensi_jenis" class="mb-1 block text-sm font-medium text-gray-700">Jenis Referensi</label>
            <select name="referensi_jenis" id="referensi_jenis" class="{{ $fieldClass }}">
                <option value="">Belum tersedia</option>
                @foreach ($referensiJenis as $value => $label)
                    <option value="{{ $value }}" @selected(old('referensi_jenis', $record?->referensi_jenis) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('referensi_jenis')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="referensi_tahun" class="mb-1 block text-sm font-medium text-gray-700">Tahun</label>
            <input type="number" name="referensi_tahun" id="referensi_tahun" min="1800" max="{{ date('Y') }}" value="{{ old('referensi_tahun', $record?->referensi_tahun) }}" class="{{ $fieldClass }}">
            @error('referensi_tahun')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label for="referensi_judul" class="mb-1 block text-sm font-medium text-gray-700">Judul Referensi</label>
            <input type="text" name="referensi_judul" id="referensi_judul" maxlength="200" value="{{ old('referensi_judul', $record?->referensi_judul) }}" class="{{ $fieldClass }}">
            @error('referensi_judul')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="referensi_penulis" class="mb-1 block text-sm font-medium text-gray-700">Penulis</label>
            <input type="text" name="referensi_penulis" id="referensi_penulis" maxlength="150" value="{{ old('referensi_penulis', $record?->referensi_penulis) }}" class="{{ $fieldClass }}">
            @error('referensi_penulis')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="referensi_url" class="mb-1 block text-sm font-medium text-gray-700">DOI / URL</label>
            <input type="url" name="referensi_url" id="referensi_url" maxlength="500" value="{{ old('referensi_url', $record?->referensi_url) }}" placeholder="https://..." class="{{ $fieldClass }}">
            @error('referensi_url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</section>

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <h2 class="mb-4 text-base font-bold text-[#173b29]">Media dan Status</h2>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <x-knowledge.status-select name="status" :value="$record?->status" default="draft" :locked="auth()->user()?->hasRole('popt') ?? false" />
        </div>
        <div>
            <label for="image" class="mb-1 block text-sm font-medium text-gray-700">Foto Gejala <span class="text-gray-400">(opsional)</span></label>
            @if ($record?->image_path)<p class="mb-2 text-xs text-gray-500">Foto saat ini tersedia. Pilih file baru untuk menggantinya.</p>@endif
            <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp" class="{{ $fieldClass }}">
            <p class="mt-1 text-xs text-gray-500">JPG, PNG, atau WebP; maksimal 5 MB.</p>
            @error('image')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</section>
