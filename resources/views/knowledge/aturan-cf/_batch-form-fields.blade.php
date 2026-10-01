@php
    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200';
    $symptomOptions = $gejalaList->map(fn ($gejala) => [
        'id' => (string) $gejala->id,
        'label' => trim(($gejala->kode ? $gejala->kode . ' — ' : '') . $gejala->nama),
    ])->values();
    $scaleOptions = collect($cfMethod?->scaleOptions() ?? [])
        ->filter(fn (array $option): bool => (float) $option['cf'] > 0)
        ->values();
    $oldGejalaIds = old('gejala_ids', []);
    $oldTerms = old('expert_terms', []);
    $oldRationales = old('expert_rationales', []);
    $initialRows = count($oldGejalaIds) > 0
        ? collect($oldGejalaIds)->values()->map(fn ($id, $index) => [
            'key' => 'old-' . $index,
            'gejalaId' => (string) $id,
            'term' => (string) ($oldTerms[$index] ?? ''),
            'rationale' => (string) ($oldRationales[$index] ?? ''),
        ])->values()
        : collect([['key' => 'new-0', 'gejalaId' => '', 'term' => '', 'rationale' => '']]);
@endphp

<div
    x-data="{
        diseaseName: '',
        symptoms: @js($initialRows),
        options: @js($symptomOptions),
        scale: @js($scaleOptions),
        rowErrors: @js($errors->toArray()),
        nextKey: 1,
        syncSearch(detail) {
            if (detail.name === 'penyakit_id') this.diseaseName = detail.label || '';
        },
        addSymptom() {
            this.symptoms.push({ key: 'new-' + this.nextKey++, gejalaId: '', term: '', rationale: '' });
        },
        removeSymptom(index) {
            if (this.symptoms.length > 1) this.symptoms.splice(index, 1);
        },
        chooseTerm(row) {
            if (!this.scale.some(option => option.term === row.term)) row.term = '';
        },
        cfFor(row) {
            const option = this.scale.find(item => item.term === row.term);
            return option ? Number(option.cf).toFixed(2) : '—';
        },
        symptomName(row) {
            return this.options.find(option => option.id === String(row.gejalaId))?.label || 'Gejala belum dipilih';
        }
    }"
    x-on:search-select-change.window="syncSearch($event.detail)"
    class="knowledge-form-sections"
>
    <section class="knowledge-form-section">
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#173b29]">Penyakit</h2>
            <p class="mt-1 text-sm text-gray-500">Pilih satu penyakit, lalu tambahkan semua gejala yang mendukungnya.</p>
        </div>
        <label for="penyakit_id" class="mb-1 block text-sm font-medium text-gray-700">Penyakit <span class="text-red-500">*</span></label>
        <x-search-select name="penyakit_id" :options="$penyakitList" selected="{{ old('penyakit_id') }}" placeholder="Cari nama atau kode penyakit..." required />
        @error('penyakit_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </section>

    <section class="knowledge-form-section">
        @include('knowledge.aturan-cf._cf-guide', ['cfMethod' => $cfMethod])
    </section>

    <section class="knowledge-form-section">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-[#173b29]">Gejala Pendukung</h2>
                <p class="mt-1 text-sm text-gray-600">Tambahkan hanya gejala yang benar-benar mendukung penyakit. Setiap gejala disimpan sebagai satu hubungan diagnosis.</p>
            </div>
            <span class="rounded-full bg-[#eaf6ee] px-3 py-1 text-xs font-semibold text-[#176b45]">Metode CF baku · 0–1</span>
        </div>

        <input type="hidden" name="cf_method_id" value="{{ $cfMethod?->id }}">
        @error('cf_method_id')<p class="mb-4 text-sm text-red-600">{{ $message }}</p>@enderror

        @error('gejala_ids')<p class="mb-4 text-sm text-red-600">{{ $message }}</p>@enderror
        <div class="space-y-5">
            <template x-for="(row, index) in symptoms" :key="row.key">
                <article class="rounded-xl border border-[#dbe5df] bg-[#fbfdfb] p-4 sm:p-5">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-[#173b29]">Gejala <span x-text="index + 1"></span></p>
                            <p class="mt-1 text-xs text-gray-500" x-text="symptomName(row)"></p>
                        </div>
                        <button type="button" x-show="symptoms.length > 1" @click="removeSymptom(index)" class="text-xs font-semibold text-red-600 hover:text-red-800">Hapus</button>
                    </div>

                    <label :for="'gejala-' + index" class="mb-1 block text-sm font-medium text-gray-700">Pilih gejala <span class="text-red-500">*</span></label>
                    <x-search-select
                        :options="$gejalaList"
                        model="row.gejalaId"
                        name-expression="'gejala_ids[' + index + ']'"
                        id-expression="'gejala-' + index"
                        disabled-expression="symptoms.filter((item, itemIndex) => itemIndex !== index).map(item => item.gejalaId)"
                        placeholder="Cari nama atau kode gejala..."
                        required
                    />
                    <p x-show="rowErrors['gejala_ids.' + index]" x-text="rowErrors['gejala_ids.' + index]?.[0]" class="mt-1 text-sm text-red-600"></p>

                    <div class="mt-5">
                        <p class="mb-2 text-sm font-medium text-gray-700">Kekuatan dukungan <span class="text-red-500">*</span></p>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                            <template x-for="option in scale" :key="option.term">
                                <label class="relative flex min-h-14 cursor-pointer items-center gap-1 rounded-xl border px-3 py-3 transition focus-within:ring-2 focus-within:ring-[#176b45]/30" :class="row.term === option.term ? 'border-[#176b45] bg-[#eaf6ee] text-[#176b45] shadow-sm' : 'border-[#dbe5df] bg-white text-[#33443a] hover:border-[#8fbea2]'">
                                    <input type="radio" :name="'expert_terms[' + index + ']'" class="mr-3 h-4 w-4 accent-[#176b45]" :value="option.term" x-model="row.term" @change="chooseTerm(row)" required :aria-label="option.term">
                                    <span class="text-sm font-semibold" x-text="option.term"></span><span class="ml-auto text-xs font-mono" x-text="Number(option.cf).toFixed(2)"></span>
                                </label>
                            </template>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Nilai ini menunjukkan kekuatan gejala mendukung penyakit, bukan tingkat keparahan gejala.</p>
                        <p x-show="rowErrors['expert_terms.' + index]" x-text="rowErrors['expert_terms.' + index]?.[0]" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <div class="mt-4 rounded-xl border border-[#b9ddc5] bg-[#f0faf3] px-4 py-3" aria-live="polite">
                        <p class="text-xs font-bold uppercase tracking-wide text-[#66746c]">CF hasil konversi</p>
                        <p class="mt-1 font-mono text-2xl font-bold text-[#176b45]" x-text="cfFor(row)"></p>
                        <input type="hidden" :name="'cf_pakar_values[' + index + ']'" :value="cfFor(row) === '—' ? '' : cfFor(row)">
                    </div>

                    <label :for="'rationale-' + index" class="mt-4 mb-1 block text-sm font-medium text-gray-700">Alasan penilaian <span class="text-red-500">*</span></label>
                    <textarea :id="'rationale-' + index" :name="'expert_rationales[' + index + ']'" x-model="row.rationale" rows="4" maxlength="5000" required class="{{ $fieldClass }}" placeholder="Jelaskan mengapa gejala ini mendukung penyakit tersebut."></textarea>
                    <p x-show="rowErrors['expert_rationales.' + index]" x-text="rowErrors['expert_rationales.' + index]?.[0]" class="mt-1 text-sm text-red-600"></p>
                </article>
            </template>
        </div>

        <button type="button" @click="addSymptom(); $nextTick(() => { const inputs = $el.closest('[x-data]').querySelectorAll('[data-symptom-search]'); inputs[inputs.length - 1]?.focus({ preventScroll: true }); })" class="mt-5 inline-flex items-center rounded-lg border border-[#8fbea2] bg-white px-4 py-2 text-sm font-semibold text-[#176b45] hover:bg-[#f0faf3]">
            <span class="mr-2 text-lg leading-none">+</span> Tambah Gejala
        </button>
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
            Jika gejala netral atau tidak mendukung, jangan tambahkan hubungan tersebut. Hapus baris gejala dan simpan hanya gejala pendukung.
        </div>
    </section>

    <section class="knowledge-form-section">
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#173b29]">Informasi Penilaian</h2>
            <p class="mt-1 text-sm text-gray-500">Informasi ini digunakan bersama untuk semua gejala yang ditambahkan.</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="expert_name" class="mb-1 block text-sm font-medium text-gray-700">Nama Pakar <span class="text-red-500">*</span></label>
                <input type="text" id="expert_name" name="expert_name" maxlength="150" value="{{ old('expert_name') }}" required class="{{ $fieldClass }}">
                @error('expert_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="expert_institution" class="mb-1 block text-sm font-medium text-gray-700">Instansi</label>
                <input type="text" id="expert_institution" name="expert_institution" maxlength="150" value="{{ old('expert_institution') }}" class="{{ $fieldClass }}">
                @error('expert_institution')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="elicited_at" class="mb-1 block text-sm font-medium text-gray-700">Tanggal <span class="text-red-500">*</span></label>
                <input type="date" id="elicited_at" name="elicited_at" value="{{ old('elicited_at', now()->format('Y-m-d')) }}" required class="{{ $fieldClass }}">
                @error('elicited_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="mt-6 border-t border-gray-200 pt-5">
            <h3 class="text-sm font-semibold text-[#173b29]">Status Knowledge</h3>
            <p class="mt-1 text-sm text-gray-500">Simpan sebagai draft atau publikasikan sesuai kewenangan akun.</p>
            <div class="mt-4"><x-knowledge.status-select name="status" default="draft" :locked="auth()->user()?->hasRole('popt') ?? false" /></div>
        </div>
    </section>

    <input type="hidden" name="jenis_sumber" value="{{ old('jenis_sumber', \App\Models\AturanCf::SOURCE_EXPERT) }}">
    <input type="hidden" name="pendekatan" value="{{ old('pendekatan', 'CF Pakar Baku 0–1') }}">
    <input type="hidden" name="status_validasi" value="{{ \App\Models\AturanCf::VALIDATION_UNVALIDATED }}">
</div>
