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
        syncSearch(detail) {
            if (detail.name === 'penyakit_id') this.diseaseName = detail.label || '';
            if (detail.name === 'gejala_id') this.symptomName = detail.label || '';
        },
        chooseMethod() {
            const option = this.method?.scale?.find(item => item.term === this.expertTerm);
            if (option) this.cfValue = Number(option.cf).toFixed(3);
        },
        chooseTerm() {
            const option = this.method?.scale?.find(item => item.term === this.expertTerm);
            this.cfValue = option ? Number(option.cf).toFixed(3) : '';
        },
        init() { this.chooseMethod(); }
    }"
    x-on:search-select-change.window="syncSearch($event.detail)"
    class="space-y-6"
>
    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#173b29]">A. Hubungan Knowledge</h2>
            <p class="mt-1 text-sm text-gray-500">Pilih hubungan penyakit dan gejala yang akan diberi bobot CF.</p>
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
                <p class="mt-1 text-xs text-gray-500">Ketik nama atau kode untuk mempercepat pencarian gejala.</p>
                @error('gejala_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-[#d6ebe0] bg-[#f7fcf9] p-5 sm:p-6">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-[#173b29]">B. Metode Penentuan CF</h2>
                <p class="mt-1 text-sm text-gray-600">Metode ini menentukan bagaimana tingkat keyakinan pakar dikonversi menjadi nilai CF.</p>
            </div>
            <a href="{{ route('knowledge.cf-methods.index') }}" class="text-sm font-semibold text-[#176b45] hover:underline">Lihat detail metode</a>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="cf_method_id" class="mb-1 block text-sm font-medium text-gray-700">Metode CF</label>
                <select id="cf_method_id" name="cf_method_id" x-model="methodId" @change="chooseMethod" class="{{ $fieldClass }}">
                    <option value="">Belum tercatat (legacy)</option>
                    @foreach($cfMethods as $method)
                        <option value="{{ $method->id }}">{{ $method->name }} · v{{ $method->version }}</option>
                    @endforeach
                </select>
                @error('cf_method_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="rounded-lg border border-[#dbece1] bg-white px-4 py-3 text-sm text-[#315e47]">
                <span class="font-semibold">Deskripsi metode</span>
                <p class="mt-1" x-text="method?.description || 'Belum tersedia. Data legacy tetap dapat dipelihara dengan nilai CF numerik.'"></p>
                <p class="mt-2 text-xs text-[#66746c]">Versi: <span x-text="method?.version || '—'"></span></p>
            </div>
            <div class="sm:col-span-2 rounded-lg border border-[#dbece1] bg-white px-4 py-3 text-sm text-[#315e47]">
                <span class="font-semibold">Referensi Metodologi CF</span>
                <p class="mt-1" x-text="method?.reference || 'Belum tersedia — isi setelah bibliografi metodologi diverifikasi.'"></p>
                <template x-if="method?.reference_url"><a class="mt-1 inline-block text-[#176b45] underline" :href="method.reference_url" target="_blank" rel="noopener noreferrer">Buka referensi</a></template>
                <p class="mt-2 text-xs text-[#66746c]">Referensi ini menjelaskan cara memperoleh nilai CF dari penilaian pakar, bukan referensi penyakit.</p>
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#173b29]">C. Pertanyaan dan Penilaian Pakar</h2>
            <p class="mt-1 text-sm text-gray-500">Pilih tingkat keyakinan pakar terhadap hubungan antara gejala dan penyakit.</p>
        </div>
        <div class="rounded-lg border border-[#e8efea] bg-[#fbfdfb] px-4 py-3 text-sm leading-6 text-[#315e47]">
            <span class="font-semibold">Pertanyaan elicitation</span>
            <p class="mt-1" x-text="method?.question ? method.question.replace('{gejala}', symptomName || '[gejala]').replace('{penyakit}', diseaseName || '[penyakit]') : 'Pilih metode Expert Elicitation untuk menampilkan pertanyaan.'"></p>
        </div>
        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <div>
                <label for="expert_term" class="mb-1 block text-sm font-medium text-gray-700">Tingkat Keyakinan Pakar</label>
                <select id="expert_term" name="expert_term" x-model="expertTerm" @change="chooseTerm" class="{{ $fieldClass }}">
                    <option value="">Belum tercatat</option>
                    <template x-for="option in (method?.scale || [])" :key="option.term">
                        <option :value="option.term" x-text="option.term"></option>
                    </template>
                </select>
                <p class="mt-1 text-xs text-gray-500">Istilah ini menggambarkan kekuatan dukungan gejala terhadap penyakit, bukan tingkat keparahan gejala.</p>
                @error('expert_term')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="cf_pakar" class="mb-1 block text-sm font-medium text-gray-700">Nilai CF <span class="text-red-500">*</span></label>
                <input type="number" id="cf_pakar" name="cf_pakar" step="0.001" min="-1" max="1" required x-model="cfValue" :readonly="method && !isSimulation" placeholder="0.000" class="{{ $fieldClass }} read-only:bg-[#f3f7f4] read-only:text-[#176b45] read-only:font-semibold">
                <p class="mt-1 text-xs text-gray-500" x-show="method && !isSimulation">Dihitung otomatis dari skala metode dan bersifat read-only.</p>
                <p class="mt-1 text-xs text-gray-500" x-show="!method || isSimulation">Untuk legacy/simulasi, nilai numerik tetap dipertahankan sesuai data yang tersedia.</p>
                @error('cf_pakar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="expert_rationale" class="mb-1 block text-sm font-medium text-gray-700">Alasan / Rationale Pakar</label>
                <textarea id="expert_rationale" name="expert_rationale" rows="4" maxlength="5000" class="{{ $fieldClass }}" placeholder="Jelaskan alasan pakar memberikan tingkat keyakinan tersebut.">{{ old('expert_rationale', $record?->expert_rationale) }}</textarea>
                @error('expert_rationale')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#173b29]">D. Provenance Penilaian</h2>
            <p class="mt-1 text-sm text-gray-500">Pakar adalah sumber expert judgment. Operator meninjau kelengkapan data dan memublikasikan Knowledge.</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="expert_name" class="mb-1 block text-sm font-medium text-gray-700">Nama Pakar Penilai</label>
                <input type="text" id="expert_name" name="expert_name" maxlength="150" value="{{ old('expert_name', $record?->expert_name) }}" class="{{ $fieldClass }}">
                @error('expert_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="expert_institution" class="mb-1 block text-sm font-medium text-gray-700">Instansi</label>
                <input type="text" id="expert_institution" name="expert_institution" maxlength="150" value="{{ old('expert_institution', $record?->expert_institution) }}" class="{{ $fieldClass }}">
                @error('expert_institution')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="elicited_at" class="mb-1 block text-sm font-medium text-gray-700">Tanggal Elicitation</label>
                <input type="date" id="elicited_at" name="elicited_at" value="{{ old('elicited_at', $record?->elicited_at?->format('Y-m-d')) }}" class="{{ $fieldClass }}">
                @error('elicited_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#173b29]">E. Metadata Legacy dan Status Knowledge</h2>
            <p class="mt-1 text-sm text-gray-500">Field ini dipertahankan untuk kompatibilitas data lama dan alur review yang sudah berjalan.</p>
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
                <label for="pendekatan" class="mb-1 block text-sm font-medium text-gray-700">Catatan Pendekatan</label>
                <input type="text" id="pendekatan" name="pendekatan" maxlength="150" value="{{ old('pendekatan', $record?->pendekatan) }}" placeholder="Contoh: Expert Elicitation" class="{{ $fieldClass }}">
                @error('pendekatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="dasar_penentuan" class="mb-1 block text-sm font-medium text-gray-700">Dasar Penentuan / Catatan Review</label>
                <textarea id="dasar_penentuan" name="dasar_penentuan" rows="3" class="{{ $fieldClass }}" placeholder="Untuk legacy/simulasi, jelaskan keterbatasan provenance bila diketahui.">{{ old('dasar_penentuan', $record?->dasar_penentuan) }}</textarea>
                @error('dasar_penentuan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="sumber" class="mb-1 block text-sm font-medium text-gray-700">Referensi Legacy</label>
                <input type="text" id="sumber" name="sumber" maxlength="150" value="{{ old('sumber', $record?->sumber) }}" class="{{ $fieldClass }}">
                @error('sumber')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="status_validasi" class="mb-1 block text-sm font-medium text-gray-700">Status Review Lama</label>
                @if(auth()->user()?->hasRole('popt'))
                    <input type="hidden" name="status_validasi" value="{{ \App\Models\AturanCf::VALIDATION_UNVALIDATED }}">
                    <div class="rounded-lg border border-[#dbece1] bg-[#f5fbf7] px-3 py-2 text-sm text-[#176b45]">Belum Divalidasi</div>
                @else
                    <select id="status_validasi" name="status_validasi" class="{{ $fieldClass }}">
                        @foreach($validationStatuses as $value => $label)<option value="{{ $value }}" @selected(old('status_validasi', $record?->status_validasi ?? \App\Models\AturanCf::VALIDATION_UNVALIDATED) === $value)>{{ $label }}</option>@endforeach
                    </select>
                @endif
                @error('status_validasi')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <x-knowledge.status-select name="status" :value="$record?->status" default="draft" :locked="auth()->user()?->hasRole('popt') ?? false" />
                <p class="mt-2 text-xs text-gray-500">Draft boleh belum lengkap. Aturan expert aktif wajib memiliki metode, tingkat keyakinan, CF hasil mapping, rationale, pakar, dan tanggal elicitation.</p>
            </div>
        </div>
    </section>
</div>
