@php
    $record = $aturanCf ?? null;
    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200';
    $selectedMethod = $record?->cf_method_id ? ($cfMethod ?? $record->cfMethod) : null;
    $isLegacyRule = $record && ! $record->cf_method_id;
    $scaleOptions = collect($selectedMethod?->scaleOptions() ?? [])
        ->filter(fn (array $option): bool => (float) $option['cf'] > 0)
        ->values();
@endphp

<div
    x-data="{
        expertTerm: @js(old('expert_term', $record?->expert_term ?? '')),
        cfValue: @js(old('cf_pakar', $record?->cf_pakar)),
        scale: @js($scaleOptions),
        chooseTerm() {
            const option = this.scale.find(item => item.term === this.expertTerm);
            this.cfValue = option ? Number(option.cf).toFixed(3) : '';
        },
        formatCf(value) {
            if (value === null || value === undefined || value === '') return '—';
            const number = Number(value);
            return Number.isFinite(number) ? number.toFixed(2) : '—';
        }
    }"
    class="space-y-6"
>
    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#173b29]">A. Hubungan Penyakit dan Gejala</h2>
            <p class="mt-1 text-sm text-gray-500">Pilih penyakit dan gejala yang sedang dinilai.</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="penyakit_id" class="mb-1 block text-sm font-medium text-gray-700">Penyakit <span class="text-red-500">*</span></label>
                <x-search-select name="penyakit_id" :options="$penyakitList" selected="{{ old('penyakit_id', $record?->penyakit_id) }}" placeholder="Cari nama atau kode penyakit..." required />
                @error('penyakit_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="gejala_id" class="mb-1 block text-sm font-medium text-gray-700">Gejala <span class="text-red-500">*</span></label>
                <x-search-select name="gejala_id" :options="$gejalaList" selected="{{ old('gejala_id', $record?->gejala_id) }}" placeholder="Cari nama atau kode gejala..." required />
                <p class="mt-1 text-xs text-gray-500">Ketik nama atau kode gejala untuk mencari dengan cepat.</p>
                @error('gejala_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    @if($isLegacyRule)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
            <span class="font-semibold">Aturan lama ini belum memiliki data tingkat keyakinan pakar.</span>
            Nilai numerik tetap dipertahankan sebagai <strong>Nilai CF Legacy: {{ number_format((float) $record->cf_pakar, 2) }}</strong>. Data ini tidak diberi referensi metodologi yang tidak tercatat.
        </div>
    @else
        @include('knowledge.aturan-cf._cf-guide', ['cfMethod' => $selectedMethod])
        <input type="hidden" name="cf_method_id" value="{{ $selectedMethod?->id }}">
        @error('cf_method_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

        <section class="rounded-xl border border-[#d6ebe0] bg-white p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-base font-bold text-[#173b29]">B. Kekuatan Hubungan</h2>
                <p class="mt-1 text-sm text-gray-600">Pilih kekuatan hubungan gejala terhadap penyakit. Nilai CF diisi otomatis dari pedoman baku.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @foreach($scaleOptions as $option)
                    <label class="relative flex min-h-14 cursor-pointer items-center rounded-xl border px-4 py-3 transition focus-within:ring-2 focus-within:ring-[#176b45]/30" :class="expertTerm === @js($option['term']) ? 'border-[#176b45] bg-[#eaf6ee] text-[#176b45] shadow-sm' : 'border-[#dbe5df] bg-white text-[#33443a] hover:border-[#8fbea2]'">
                        <input type="radio" name="expert_term" class="mr-3 h-4 w-4 accent-[#176b45]" value="{{ $option['term'] }}" x-model="expertTerm" @change="chooseTerm" aria-label="{{ $option['term'] }}">
                        <span class="text-sm font-semibold">{{ $option['term'] }}</span>
                    </label>
                @endforeach
            </div>
            @error('expert_term')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            <div class="mt-5 rounded-xl border border-[#b9ddc5] bg-[#f0faf3] px-5 py-4" aria-live="polite">
                <p class="text-xs font-bold uppercase tracking-wide text-[#66746c]">Nilai CF Otomatis</p>
                <p class="mt-1 font-mono text-3xl font-bold text-[#176b45]" x-text="formatCf(cfValue)"></p>
                <p class="mt-1 text-xs text-[#526159]">Nilai ini mengikuti istilah kekuatan yang dipilih dari metode baku.</p>
                <input type="hidden" name="cf_pakar" x-model="cfValue">
            </div>
        </section>
    @endif

    @if($isLegacyRule)
        <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="text-base font-bold text-[#173b29]">B. Nilai Legacy</h2>
            <label for="cf_pakar_legacy" class="mt-4 mb-1 block text-sm font-semibold text-gray-700">Nilai CF tersimpan</label>
            <input type="number" id="cf_pakar_legacy" name="cf_pakar" step="0.001" min="-1" max="1" value="{{ old('cf_pakar', $record?->cf_pakar) }}" class="{{ $fieldClass }}">
            @error('cf_pakar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </section>
    @endif

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#173b29]">C. Alasan Penilaian Pakar</h2>
            <p class="mt-1 text-sm text-gray-500">Jelaskan alasan mengapa gejala tersebut memiliki kekuatan hubungan tersebut terhadap penyakit.</p>
        </div>
        <textarea id="expert_rationale" name="expert_rationale" rows="4" maxlength="5000" class="{{ $fieldClass }}" placeholder="Tuliskan alasan penilaian pakar.">{{ old('expert_rationale', $record?->expert_rationale) }}</textarea>
        @error('expert_rationale')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#173b29]">D. Informasi Penilaian</h2>
            <p class="mt-1 text-sm text-gray-500">Catat sumber penilaian pakar dan waktunya.</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label for="expert_name" class="mb-1 block text-sm font-medium text-gray-700">Pakar Penilai</label><input type="text" id="expert_name" name="expert_name" maxlength="150" value="{{ old('expert_name', $record?->expert_name) }}" class="{{ $fieldClass }}">@error('expert_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="expert_institution" class="mb-1 block text-sm font-medium text-gray-700">Instansi</label><input type="text" id="expert_institution" name="expert_institution" maxlength="150" value="{{ old('expert_institution', $record?->expert_institution) }}" class="{{ $fieldClass }}">@error('expert_institution')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="elicited_at" class="mb-1 block text-sm font-medium text-gray-700">Tanggal Penilaian</label><input type="date" id="elicited_at" name="elicited_at" value="{{ old('elicited_at', $record?->elicited_at?->format('Y-m-d')) }}" class="{{ $fieldClass }}">@error('elicited_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="text-base font-bold text-[#173b29]">E. Status Knowledge</h2>
        <p class="mt-1 text-sm text-gray-500">Simpan sebagai draft untuk dilengkapi, atau publikasikan sesuai kewenangan akun.</p>
        <div class="mt-4"><x-knowledge.status-select name="status" :value="$record?->status" default="draft" :locked="auth()->user()?->hasRole('popt') ?? false" /></div>
    </section>

    <input type="hidden" name="jenis_sumber" value="{{ old('jenis_sumber', $record?->jenis_sumber) }}">
    <input type="hidden" name="pendekatan" value="{{ old('pendekatan', $record?->pendekatan) }}">
    <input type="hidden" name="dasar_penentuan" value="{{ old('dasar_penentuan', $record?->dasar_penentuan) }}">
    <input type="hidden" name="sumber" value="{{ old('sumber', $record?->sumber) }}">
    @if(auth()->user()?->hasRole('popt'))
        <input type="hidden" name="status_validasi" value="{{ \App\Models\AturanCf::VALIDATION_UNVALIDATED }}">
    @else
        <input type="hidden" name="status_validasi" value="{{ old('status_validasi', $record?->status_validasi ?? \App\Models\AturanCf::VALIDATION_UNVALIDATED) }}">
    @endif
</div>
