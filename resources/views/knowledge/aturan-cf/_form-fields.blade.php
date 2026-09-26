@php
    $record = $aturanCf ?? null;
    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200';
@endphp

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <div class="mb-5">
        <h2 class="text-base font-bold text-[#173b29]">A. Relasi Aturan</h2>
        <p class="mt-1 text-sm text-gray-500">Pilih hubungan penyakit dan gejala yang akan diberi bobot CF.</p>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="penyakit_id" class="mb-1 block text-sm font-medium text-gray-700">Penyakit <span class="text-red-500">*</span></label>
            <x-search-select name="penyakit_id" :options="$penyakitList" selected="{{ old('penyakit_id', $record?->penyakit_id) }}" placeholder="Cari penyakit..." required />
            @error('penyakit_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="gejala_id" class="mb-1 block text-sm font-medium text-gray-700">Gejala <span class="text-red-500">*</span></label>
            <x-search-select name="gejala_id" :options="$gejalaList" selected="{{ old('gejala_id', $record?->gejala_id) }}" placeholder="Cari gejala..." required />
            @error('gejala_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</section>

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <div class="mb-5">
        <h2 class="text-base font-bold text-[#173b29]">B. Nilai CF</h2>
        <p class="mt-1 text-sm text-gray-500">CF mengukur kekuatan dukungan gejala terhadap penyakit, bukan tingkat keparahan gejala.</p>
    </div>
    <div>
        <label for="cf_pakar" class="mb-1 block text-sm font-medium text-gray-700">CF Pakar <span class="text-red-500">*</span></label>
        <input type="number" id="cf_pakar" name="cf_pakar" step="0.001" min="-1" max="1" required value="{{ old('cf_pakar', $record?->cf_pakar) }}" placeholder="0.000" class="{{ $fieldClass }}">
        <p class="mt-1 text-xs text-gray-500">Rentang sistem: -1 sampai 1. Nilai tetap dimasukkan sesuai metodologi Knowledge SIPAKARBUN.</p>
        @error('cf_pakar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</section>

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <div class="mb-5">
        <h2 class="text-base font-bold text-[#173b29]">C. Dasar Penentuan</h2>
        <p class="mt-1 text-sm text-gray-500">Jelaskan asal, metode, dan alasan nilai CF tanpa membuat klaim ilmiah otomatis.</p>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="jenis_sumber" class="mb-1 block text-sm font-medium text-gray-700">Jenis Sumber</label>
            <select id="jenis_sumber" name="jenis_sumber" class="{{ $fieldClass }}">
                <option value="">Belum tersedia</option>
                @foreach($sourceTypes as $value => $label)<option value="{{ $value }}" @selected(old('jenis_sumber', $record?->jenis_sumber) === $value)>{{ $label }}</option>@endforeach
            </select>
            @error('jenis_sumber')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="pendekatan" class="mb-1 block text-sm font-medium text-gray-700">Metode Penentuan CF</label>
            <input type="text" id="pendekatan" name="pendekatan" maxlength="150" value="{{ old('pendekatan', $record?->pendekatan) }}" placeholder="Contoh: Expert Elicitation + Literature Review" class="{{ $fieldClass }}">
            @error('pendekatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label for="dasar_penentuan" class="mb-1 block text-sm font-medium text-gray-700">Dasar / Alasan Penentuan CF</label>
            <textarea id="dasar_penentuan" name="dasar_penentuan" rows="5" class="{{ $fieldClass }}" placeholder="Jelaskan dasar penilaian secara ringkas dan dapat ditelusuri.">{{ old('dasar_penentuan', $record?->dasar_penentuan) }}</textarea>
            @error('dasar_penentuan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</section>

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <div class="mb-5">
        <h2 class="text-base font-bold text-[#173b29]">D. Referensi</h2>
        <p class="mt-1 text-sm text-gray-500">DOI/URL bersifat opsional jika sitasi bibliografi sudah memadai.</p>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="sumber" class="mb-1 block text-sm font-medium text-gray-700">Judul Referensi</label>
            <input type="text" id="sumber" name="sumber" maxlength="150" value="{{ old('sumber', $record?->sumber) }}" class="{{ $fieldClass }}">
            @error('sumber')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="referensi_penulis" class="mb-1 block text-sm font-medium text-gray-700">Penulis</label>
            <input type="text" id="referensi_penulis" name="referensi_penulis" maxlength="150" value="{{ old('referensi_penulis', $record?->referensi_penulis) }}" class="{{ $fieldClass }}">
            @error('referensi_penulis')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="referensi_tahun" class="mb-1 block text-sm font-medium text-gray-700">Tahun</label>
            <input type="number" id="referensi_tahun" name="referensi_tahun" min="1800" max="{{ date('Y') }}" value="{{ old('referensi_tahun', $record?->referensi_tahun) }}" class="{{ $fieldClass }}">
            @error('referensi_tahun')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label for="referensi_url" class="mb-1 block text-sm font-medium text-gray-700">DOI / URL</label>
            <input type="url" id="referensi_url" name="referensi_url" maxlength="500" value="{{ old('referensi_url', $record?->referensi_url) }}" placeholder="https://..." class="{{ $fieldClass }}">
            @error('referensi_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</section>

<section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
    <div class="mb-5">
        <h2 class="text-base font-bold text-[#173b29]">E. Validasi</h2>
        <p class="mt-1 text-sm text-gray-500">Validator merupakan metadata Knowledge, bukan role akun baru.</p>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="validator_nama" class="mb-1 block text-sm font-medium text-gray-700">Nama Validator</label>
            <input type="text" id="validator_nama" name="validator_nama" maxlength="150" value="{{ old('validator_nama', $record?->validator_nama) }}" class="{{ $fieldClass }}">
            @error('validator_nama')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="validator_instansi" class="mb-1 block text-sm font-medium text-gray-700">Instansi Validator</label>
            <input type="text" id="validator_instansi" name="validator_instansi" maxlength="150" value="{{ old('validator_instansi', $record?->validator_instansi) }}" class="{{ $fieldClass }}">
            @error('validator_instansi')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="tanggal_validasi" class="mb-1 block text-sm font-medium text-gray-700">Tanggal Validasi</label>
            <input type="date" id="tanggal_validasi" name="tanggal_validasi" value="{{ old('tanggal_validasi', $record?->tanggal_validasi?->format('Y-m-d')) }}" class="{{ $fieldClass }}">
            @error('tanggal_validasi')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="status_validasi" class="mb-1 block text-sm font-medium text-gray-700">Status Validasi</label>
            @if(auth()->user()?->hasRole('popt'))
                <input type="hidden" name="status_validasi" value="{{ \App\Models\AturanCf::VALIDATION_UNVALIDATED }}">
                <div class="rounded-lg border border-[#dbece1] bg-[#f5fbf7] px-3 py-2 text-sm text-[#176b45]">Belum Divalidasi (ditinjau Admin / Operator UPTD)</div>
            @else
                <select id="status_validasi" name="status_validasi" class="{{ $fieldClass }}">
                    @foreach($validationStatuses as $value => $label)<option value="{{ $value }}" @selected(old('status_validasi', $record?->status_validasi ?? \App\Models\AturanCf::VALIDATION_UNVALIDATED) === $value)>{{ $label }}</option>@endforeach
                </select>
            @endif
            @error('status_validasi')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <x-knowledge.status-select name="status" :value="$record?->status" default="draft" :locked="auth()->user()?->hasRole('popt') ?? false" />
            <p class="mt-2 text-xs text-gray-500">Draft boleh belum lengkap. Aturan aktif wajib memiliki jenis sumber, metode, dan alasan CF.</p>
        </div>
    </div>
</section>
