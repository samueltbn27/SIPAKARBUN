@php
    $record = $aturanCf ?? null;
    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200';
    $methodData = $cfMethods->map(fn ($method) => [
        'id' => (string) $method->id,
        'name' => $method->name,
        'description' => $method->description,
        'version' => $method->version,
        'question' => $method->elicitation_question_template,
        'scale' => $method->scaleOptions(),
        'reference' => $method->referenceLabel(),
        'reference_url' => $method->reference_url,
    ])->values();
    $selectedMethod = (string) old('cf_method_id', $record?->cf_method_id ?? '');
    $isLegacyRule = $record && ! $record->cf_method_id;
@endphp

<div
    x-data="{
        diseaseName: @js($record?->penyakit?->nama ?? ''),
        symptomName: @js($record?->gejala?->nama ?? ''),
        methodId: @js($selectedMethod),
        expertTerm: @js(old('expert_term', $record?->expert_term ?? '')),
        cfValue: @js(old('cf_pakar', $record?->cf_pakar)),
        methods: @js($methodData),
        get method() { return this.methods.find(item => item.id === String(this.methodId)) || null; },
        get isSimulation() { return this.method?.name === @js(\App\Models\CfMethod::SIMULATION_METHOD_NAME); },
        get confidenceGroups() {
            const scale = this.method?.scale || [];
            return [
                { key: 'negative', label: 'Tidak Mendukung', options: scale.filter(item => Number(item.cf) < 0) },
                { key: 'neutral', label: 'Netral', options: scale.filter(item => Number(item.cf) === 0) },
                { key: 'positive', label: 'Mendukung', options: scale.filter(item => Number(item.cf) > 0) },
            ];
        },
        formatCf(value) {
            if (value === null || value === undefined || value === '') return '—';
            const number = Number(value);
            return Number.isFinite(number) ? number.toFixed(2) : '—';
        },
        syncSearch(detail) {
            if (detail.name === 'penyakit_id') this.diseaseName = detail.label || '';
            if (detail.name === 'gejala_id') this.symptomName = detail.label || '';
        },
        chooseMethod() {
            const available = this.isSimulation ? [] : (this.method?.scale || []);
            if (!available.some(item => item.term === this.expertTerm)) this.expertTerm = '';
            this.chooseTerm();
        },
        chooseTerm() {
            const option = this.method?.scale?.find(item => item.term === this.expertTerm);
            this.cfValue = option ? Number(option.cf).toFixed(3) : (this.method && !this.isSimulation ? '' : this.cfValue);
        },
        init() { this.chooseMethod(); }
    }"
    x-on:search-select-change.window="syncSearch($event.detail)"
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
            Nilai numerik tetap dipertahankan sebagai <strong>Nilai CF Legacy: {{ number_format((float) $record->cf_pakar, 2) }}</strong>. Pilih metode dan tingkat keyakinan jika ingin mengadopsi elicitation.
        </div>
    @endif

    <section class="rounded-xl border border-[#d6ebe0] bg-[#f7fcf9] p-5 sm:p-6">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-[#173b29]">B. Metode Penentuan CF</h2>
                <p class="mt-1 text-sm text-gray-600">Pilih metode yang digunakan untuk mengubah tingkat keyakinan pakar menjadi nilai Certainty Factor.</p>
            </div>
            <a href="{{ route('knowledge.cf-methods.index') }}" class="text-sm font-semibold text-[#176b45] hover:underline">Lihat Detail Metode</a>
        </div>
        <div class="grid gap-4 sm:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
            <div>
                <label for="cf_method_id" class="mb-1 block text-sm font-medium text-gray-700">Metode Penentuan CF</label>
                <select id="cf_method_id" name="cf_method_id" x-model="methodId" @change="methodId = $event.target.value; chooseMethod()" class="{{ $fieldClass }}">
                    <option value="">Belum tercatat (legacy)</option>
                    @foreach($cfMethods as $method)
                        <option value="{{ $method->id }}">{{ $method->name }} · v{{ $method->version }}</option>
                    @endforeach
                </select>
                @error('cf_method_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="rounded-lg border border-[#dbece1] bg-white px-4 py-3 text-sm text-[#315e47]">
                <p x-text="method?.description || 'Pilih metode untuk menampilkan cara penilaiannya.'"></p>
                <p class="mt-2 text-xs text-[#66746c]">Versi: <span x-text="method?.version || '—'"></span> · Referensi metodologi: <span x-text="method?.reference || 'Belum tersedia'"></span></p>
            </div>
        </div>
        <p class="mt-3 text-xs text-[#66746c]">Referensi metodologi menjelaskan cara memperoleh nilai CF dari penilaian pakar, bukan referensi penyakit.</p>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#173b29]">C. Pertanyaan untuk Pakar</h2>
            <p class="mt-1 text-sm text-gray-500">Pertanyaan ini membantu menilai kekuatan hubungan gejala terhadap penyakit.</p>
        </div>
        <div x-show="method && diseaseName && symptomName" x-cloak class="rounded-lg border border-[#dbece1] bg-[#f7fcf9] px-4 py-4 text-sm leading-6 text-[#315e47]" aria-live="polite">
            <p x-text="method?.question?.replace('{gejala}', symptomName).replace('{penyakit}', diseaseName)"></p>
        </div>
        <div x-show="!method || !diseaseName || !symptomName" class="rounded-lg border border-dashed border-gray-300 px-4 py-4 text-sm text-gray-500">
            Pilih penyakit, gejala, dan metode untuk menampilkan pertanyaan penilaian.
        </div>
    </section>

    <section class="rounded-xl border border-[#d6ebe0] bg-white p-5 sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#173b29]">D. Tingkat Keyakinan Pakar</h2>
            <p class="mt-1 text-sm text-gray-600">Pilih tingkat keyakinan pakar. Nilai CF akan dihitung otomatis.</p>
        </div>
        <div x-show="method && !isSimulation && method.scale?.length" class="space-y-5">
            <template x-for="group in confidenceGroups" :key="group.key">
                <div x-show="group.options.length">
                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-[#66746c]" x-text="group.label"></p>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <template x-for="option in group.options" :key="option.term">
                            <label class="relative flex min-h-14 cursor-pointer items-center rounded-xl border px-4 py-3 transition focus-within:ring-2 focus-within:ring-[#176b45]/30" :class="expertTerm === option.term ? 'border-[#176b45] bg-[#eaf6ee] text-[#176b45] shadow-sm' : 'border-[#dbe5df] bg-white text-[#33443a] hover:border-[#8fbea2]'">
                                <input type="radio" name="expert_term" class="mr-3 h-4 w-4 accent-[#176b45]" :value="option.term" x-model="expertTerm" @change="chooseTerm" :aria-label="option.term">
                                <span class="text-sm font-semibold" x-text="option.term"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </template>
            @error('expert_term')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div x-show="!method" class="rounded-lg border border-dashed border-gray-300 px-4 py-4 text-sm text-gray-500">
            Pilih Metode Penentuan CF untuk menampilkan pilihan tingkat keyakinan pakar.
        </div>
        <div x-show="method && isSimulation" class="rounded-lg border border-dashed border-amber-300 bg-amber-50 px-4 py-4 text-sm text-amber-900">
            Metode Simulation / UAT menggunakan input manual untuk kebutuhan pengujian. Pilihan tingkat keyakinan pakar tidak digunakan.
        </div>
        <template x-if="method && !isSimulation">
            <div class="mt-5 rounded-xl border border-[#b9ddc5] bg-[#f0faf3] px-5 py-4" aria-live="polite">
                <p class="text-xs font-bold uppercase tracking-wide text-[#66746c]">Nilai CF Hasil Konversi</p>
                <p class="mt-1 font-mono text-3xl font-bold text-[#176b45]" x-text="formatCf(cfValue)"></p>
                <p class="mt-1 text-xs text-[#526159]">Nilai CF ditentukan otomatis berdasarkan tingkat keyakinan dan metode CF yang dipilih.</p>
                <input type="hidden" name="cf_pakar" x-model="cfValue">
            </div>
        </template>
        <template x-if="!method || isSimulation">
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
                <label for="cf_pakar_legacy" class="mb-1 block text-sm font-semibold text-amber-900">Nilai CF Legacy / Simulasi</label>
                <input type="number" id="cf_pakar_legacy" name="cf_pakar" step="0.001" min="-1" max="1" x-model="cfValue" class="{{ $fieldClass }} bg-white">
                <p class="mt-1 text-xs text-amber-800">Input manual hanya tersedia untuk data legacy atau simulasi. Aturan dengan metode elicitation memakai hasil konversi otomatis.</p>
            </div>
        </template>
        @error('cf_pakar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#173b29]">E. Alasan Penilaian Pakar</h2>
            <p class="mt-1 text-sm text-gray-500">Jelaskan alasan mengapa gejala tersebut dinilai memiliki tingkat keyakinan tersebut terhadap penyakit.</p>
        </div>
        <textarea id="expert_rationale" name="expert_rationale" rows="4" maxlength="5000" class="{{ $fieldClass }}" placeholder="Tuliskan alasan penilaian pakar.">{{ old('expert_rationale', $record?->expert_rationale) }}</textarea>
        @error('expert_rationale')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#173b29]">F. Informasi Penilaian</h2>
            <p class="mt-1 text-sm text-gray-500">Catat sumber penilaian pakar dan waktunya.</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="expert_name" class="mb-1 block text-sm font-medium text-gray-700">Pakar Penilai</label>
                <input type="text" id="expert_name" name="expert_name" maxlength="150" value="{{ old('expert_name', $record?->expert_name) }}" class="{{ $fieldClass }}">
                @error('expert_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="expert_institution" class="mb-1 block text-sm font-medium text-gray-700">Instansi</label>
                <input type="text" id="expert_institution" name="expert_institution" maxlength="150" value="{{ old('expert_institution', $record?->expert_institution) }}" class="{{ $fieldClass }}">
                @error('expert_institution')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="elicited_at" class="mb-1 block text-sm font-medium text-gray-700">Tanggal Penilaian</label>
                <input type="date" id="elicited_at" name="elicited_at" value="{{ old('elicited_at', $record?->elicited_at?->format('Y-m-d')) }}" class="{{ $fieldClass }}">
                @error('elicited_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="text-base font-bold text-[#173b29]">G. Status Knowledge</h2>
        <p class="mt-1 text-sm text-gray-500">Simpan sebagai draft untuk dilengkapi, atau publikasikan sesuai kewenangan akun.</p>
        <div class="mt-4">
            <x-knowledge.status-select name="status" :value="$record?->status" default="draft" :locked="auth()->user()?->hasRole('popt') ?? false" />
        </div>
    </section>

    {{-- Legacy fields stay submitted for backward compatibility but are not part of the normal UX. --}}
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
