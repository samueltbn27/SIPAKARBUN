@extends('layouts.app')

@section('title', 'Diagnosis Penyakit')

@section('content')
    <x-page-header
        title="Diagnosis Penyakit"
        subtitle="Deteksi dugaan penyakit perkebunan berbasis gejala yang Anda amati (Certainty Factor)."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Diagnosis']]"
    />

    {{-- State ERROR: knowledge API gagal --}}
    @if ($knowledgeError)
        <x-card class="flex flex-col items-center justify-center px-6 py-16 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-red-600">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </span>
            <h2 class="mt-4 text-lg font-bold text-[#173b29]">{{ $knowledgeError }}</h2>
            <p class="mt-1 max-w-md text-sm text-[#66746c]">Pastikan layanan knowledge tersedia, lalu muat ulang halaman.</p>
            <a href="{{ route('diagnosis.index') }}"
               class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#173b29]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                Muat Ulang
            </a>
        </x-card>
    @else
        {{-- State VALIDATION ERROR --}}
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-data="{ show: true }" x-show="show" x-transition>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-semibold">Periksa kembali input Anda:</p>
                        <ul class="mt-1 list-disc space-y-1 pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" @click="show = false" class="text-red-500 hover:text-red-700">&times;</button>
                </div>
            </div>
        @endif

        <div
            x-data="{
                komoditas: {{ Js::from($komoditas) }},
                gejala: {{ Js::from($gejala) }},
                gejalaMap: {{ Js::from($komoditasGejalaMap) }},
                levels: [
                    { label: 'Tidak Yakin', value: 0.2 },
                    { label: 'Kurang Yakin', value: 0.4 },
                    { label: 'Cukup Yakin', value: 0.6 },
                    { label: 'Yakin', value: 0.8 },
                    { label: 'Sangat Yakin', value: 1.0 },
                ],
                steps: [
                    { n: 1, label: 'Komoditas' },
                    { n: 2, label: 'Gejala' },
                    { n: 3, label: 'Keyakinan' },
                    { n: 4, label: 'Proses' },
                ],
                step: {{ $initialStep ?? 1 }},
                maxVisited: {{ $initialStep ?? 1 }},
                commodityId: {{ Js::from(old('commodity_id')) }},
                komoditasSearch: '',
                gejalaSearch: '',
                selected: {{ Js::from(old('symptom_ids', [])) }},
                confidences: {{ Js::from(old('symptom_confidence', [])) }},
                submitting: false,
                gejalaLoading: false,
                error: '',

                init() {
                    this.selected = this.selected.map(Number);
                    this.selected.forEach(id => { if (!(id in this.confidences)) this.confidences[id] = 0.8; });
                },
                filteredKomoditas() {
                    const q = this.komoditasSearch.trim().toLowerCase();
                    return this.komoditas.filter(k => !q || `${k.nama} ${k.kode ?? ''}`.toLowerCase().includes(q));
                },
                komoditasNama(id) {
                    const k = this.komoditas.find(x => Number(x.id) === Number(id));
                    return k ? k.nama : '—';
                },
                gejalaIds() {
                    return (this.gejalaMap[this.commodityId] || []).map(Number);
                },
                filteredGejala() {
                    const ids = this.gejalaIds();
                    const q = this.gejalaSearch.trim().toLowerCase();
                    return this.gejala.filter(g => ids.includes(Number(g.id)) && (!q || `${g.nama} ${g.kode ?? ''}`.toLowerCase().includes(q)));
                },
                isSelected(id) { return this.selected.includes(Number(id)); },
                toggleGejala(id) {
                    id = Number(id);
                    const i = this.selected.indexOf(id);
                    if (i > -1) { this.selected.splice(i, 1); delete this.confidences[id]; }
                    else { this.selected.push(id); if (!(id in this.confidences)) this.confidences[id] = 0.8; }
                },
                confValue(id) { return this.confidences[Number(id)] ?? 0.8; },
                setConf(id, value) { this.confidences[Number(id)] = value; },
                confLabel(id) {
                    const v = this.confValue(id);
                    const lvl = this.levels.find(l => l.value === v);
                    return lvl ? lvl.label : String(v);
                },
                confIndex(id) { return this.levels.findIndex(l => l.value === this.confValue(id)); },
                goTo(n) { if (n >= 1 && n <= this.maxVisited) this.step = n; },
                next() {
                    if (this.step === 1 && !this.commodityId) { this.error = 'Pilih komoditas terlebih dahulu.'; return; }
                    if (this.step === 2 && this.selected.length === 0) { this.error = 'Pilih minimal satu gejala.'; return; }
                    this.error = '';
                    if (this.step < 4) { this.step++; this.maxVisited = Math.max(this.maxVisited, this.step); }
                },
                prev() { if (this.step > 1) this.step--; },
                reset() {
                    this.commodityId = ''; this.selected = []; this.confidences = {};
                    this.error = ''; this.step = 1; this.maxVisited = 1;
                },
                pilihKomoditas(id) {
                    id = Number(id);
                    if (Number(this.commodityId) === id) {
                        // Komoditas sama: gejala sudah dimuat, cukup lanjut ke Step 2.
                        this.step = 2;
                        this.maxVisited = Math.max(this.maxVisited, 2);
                        return;
                    }
                    // Komoditas berubah → reset gejala/keyakinan sebelumnya,
                    // tampilkan loading gejala, lalu lanjut ke Step 2.
                    this.commodityId = id;
                    this.selected = [];
                    this.confidences = {};
                    this.gejalaSearch = '';
                    this.error = '';
                    this.gejalaLoading = true;
                    this.step = 2;
                    this.maxVisited = Math.max(this.maxVisited, 2);
                    setTimeout(() => { this.gejalaLoading = false; }, 450);
                },
            }"
        >
            <form method="POST" action="{{ route('diagnosis.store') }}" @submit="submitting = true">
                @csrf

                <input type="hidden" name="commodity_id" :value="commodityId || ''">

                <template x-for="id in selected" :key="'s' + id">
                    <input type="hidden" name="symptom_ids[]" :value="id">
                </template>

                <template x-for="id in selected" :key="'c' + id">
                    <input type="hidden" :name="'symptom_confidence[' + id + ']'" :value="confValue(id)">
                </template>

                {{-- Step indicator --}}
                <ol class="mb-6 flex flex-wrap items-center gap-2 sm:gap-3" aria-label="Langkah diagnosis">
                    <template x-for="s in steps" :key="s.n">
                        <li class="flex items-center gap-2 sm:gap-3">
                            <button type="button" @click="goTo(s.n)"
                                    class="flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors"
                                    :class="step === s.n
                                        ? 'border-[#176b45] bg-[#e8f4ed] text-[#176b45]'
                                        : (s.n < maxVisited ? 'border-[#176b45]/30 bg-white text-[#176b45] hover:bg-[#f3f8f4]' : 'border-[#e4ece7] bg-white text-[#a0aba4]')">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full text-[10px]"
                                      :class="step === s.n ? 'bg-[#176b45] text-white' : (s.n < maxVisited ? 'bg-[#e8f4ed] text-[#176b45]' : 'bg-[#eef3ef] text-[#a0aba4]')"
                                      x-text="s.n"></span>
                                <span class="hidden sm:inline" x-text="s.label"></span>
                            </button>
                            <template x-if="s.n < 4">
                                <svg class="h-3 w-3 text-[#b9c4bd]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </template>
                        </li>
                    </template>
                </ol>

                {{-- Inline error --}}
                <div x-show="error" x-cloak class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" x-text="error"></div>

                {{-- STEP 1: Pilih Komoditas --}}
                <section x-show="step === 1" x-cloak>
                    <x-card>
                        <h2 class="text-base font-bold text-[#173b29]">Step 1 · Pilih Komoditas</h2>
                        <p class="mt-1 text-sm text-[#66746c]">Pilih komoditas yang sedang Anda amati.</p>

                        <div class="mt-4">
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#a0aba4]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                                <input type="search" x-model="komoditasSearch" placeholder="Cari komoditas…"
                                       class="w-full rounded-xl border border-[#dbe5df] bg-white py-2.5 pl-10 pr-4 text-sm outline-none transition-colors focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15">
                            </div>

                            {{-- Loading state (data sedang dimuat dari sumber knowledge) --}}
                            <div x-show="filteredKomoditas().length === 0 && komoditas.length > 0"
                                 class="mt-4 flex items-center justify-center gap-3 rounded-xl border border-dashed border-[#dbe5df] bg-[#fafcfb] px-4 py-10 text-sm text-[#8a9990]">
                                <span class="h-5 w-5 animate-spin rounded-full border-2 border-[#d7e5dc] border-t-[#176b45]"></span>
                                Memuat komoditas…
                            </div>

                            {{-- Empty state --}}
                            <div x-show="komoditas.length === 0" x-cloak
                                 class="mt-4 rounded-xl border border-dashed border-[#dbe5df] bg-[#fafcfb] px-4 py-10 text-center text-sm text-[#8a9990]">
                                Belum ada komoditas yang tersedia.
                            </div>

                            <div x-show="filteredKomoditas().length > 0" x-cloak class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <template x-for="k in filteredKomoditas()" :key="k.id">
                                    <button type="button" @click="pilihKomoditas(k.id)"
                                            class="soft-card group flex items-center justify-between gap-3 rounded-xl border bg-white p-4 text-left transition-colors"
                                            :class="Number(commodityId) === Number(k.id) ? 'border-[#176b45] ring-2 ring-[#176b45]/15' : 'border-[#e4ece7] hover:border-[#176b45]/40'">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-bold text-[#173b29]" x-text="k.nama"></span>
                                            <span class="block text-xs text-[#8a9990]" x-text="k.kode ?? ''"></span>
                                        </span>
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border"
                                              :class="Number(commodityId) === Number(k.id) ? 'border-[#176b45] bg-[#176b45] text-white' : 'border-[#dbe5df] text-transparent'">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </x-card>
                </section>

                {{-- STEP 2: Pilih Gejala --}}
                <section x-show="step === 2" x-cloak>
                    <x-card>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-base font-bold text-[#173b29]">Step 2 · Pilih Gejala</h2>
                                <p class="mt-1 text-sm text-[#66746c]">
                                    Komoditas: <span class="font-semibold text-[#176b45]" x-text="komoditasNama(commodityId)"></span>
                                </p>
                            </div>
                            <div class="relative sm:w-64">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#a0aba4]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                                <input type="search" x-model="gejalaSearch" placeholder="Cari gejala…"
                                       class="w-full rounded-xl border border-[#dbe5df] bg-white py-2.5 pl-10 pr-4 text-sm outline-none transition-colors focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15">
                            </div>
                        </div>

                        {{-- Loading state: gejala sedang disiapkan untuk komoditas terpilih --}}
                        <div x-show="gejalaLoading" x-cloak
                             class="mt-4 flex items-center justify-center gap-3 rounded-xl border border-dashed border-[#dbe5df] bg-[#fafcfb] px-4 py-12 text-sm text-[#8a9990]">
                            <span class="h-5 w-5 animate-spin rounded-full border-2 border-[#d7e5dc] border-t-[#176b45]"></span>
                            Memuat gejala untuk komoditas ini…
                        </div>

                        {{-- Empty state: komoditas tanpa gejala --}}
                        <div x-show="!gejalaLoading && filteredGejala().length === 0" x-cloak
                             class="mt-4 rounded-xl border border-dashed border-[#dbe5df] bg-[#fafcfb] px-4 py-12 text-center">
                            <p class="text-sm font-semibold text-[#66746c]">Tidak ada gejala untuk komoditas ini</p>
                            <p class="mt-1 text-xs text-[#8a9990]">Coba pilih komoditas lain.</p>
                            <button type="button" @click="prev()" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-4 py-2 text-sm font-semibold text-white hover:bg-[#173b29]">Kembali</button>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2" x-show="!gejalaLoading && filteredGejala().length > 0" x-cloak>
                            <template x-for="g in filteredGejala()" :key="g.id">
                                <label class="soft-card flex cursor-pointer items-start gap-3 rounded-xl border bg-white p-4 transition-colors"
                                       :class="isSelected(g.id) ? 'border-[#176b45] ring-2 ring-[#176b45]/15' : 'border-[#e4ece7] hover:border-[#176b45]/40'">
                                    <input type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 rounded border-[#dbe5df] text-[#176b45] focus:ring-[#176b45]/15"
                                           :checked="isSelected(g.id)" @change="toggleGejala(g.id)">
                                    <span class="min-w-0">
                                        <span class="flex items-center gap-2">
                                            <span class="text-sm font-bold text-[#173b29]" x-text="g.nama"></span>
                                            <span class="rounded bg-[#eef3ef] px-1.5 py-0.5 text-[10px] font-semibold text-[#8a9990]" x-text="g.kode ?? ''"></span>
                                        </span>
                                        <span x-show="g.deskripsi" x-cloak class="mt-1 block text-xs leading-relaxed text-[#66746c]" x-text="g.deskripsi"></span>
                                    </span>
                                </label>
                            </template>
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-[#eef3ef] pt-4">
                            <span class="text-xs font-semibold text-[#8a9990]" x-text="selected.length + ' gejala dipilih'"></span>
                            <div class="flex gap-2">
                                <button type="button" @click="prev()" class="rounded-xl border border-[#dbe5df] bg-white px-4 py-2 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">Kembali</button>
                                <button type="button" @click="next()" class="rounded-xl bg-[#176b45] px-4 py-2 text-sm font-semibold text-white hover:bg-[#173b29]">Lanjut</button>
                            </div>
                        </div>
                    </x-card>
                </section>

                {{-- STEP 3: Tingkat Keyakinan --}}
                <section x-show="step === 3" x-cloak>
                    <x-card>
                        <h2 class="text-base font-bold text-[#173b29]">Step 3 · Tingkat Keyakinan</h2>
                        <p class="mt-1 text-sm text-[#66746c]">Seberapa yakin Anda mengamati tiap gejala? Nilai dipakai langsung oleh mesin Certainty Factor backend.</p>

                        <div x-show="selected.length === 0" x-cloak
                             class="mt-4 rounded-xl border border-dashed border-[#dbe5df] bg-[#fafcfb] px-4 py-12 text-center text-sm text-[#8a9990]">
                            Belum ada gejala yang dipilih.
                        </div>

                        <ul class="mt-4 space-y-4" x-show="selected.length > 0" x-cloak>
                            <template x-for="id in selected" :key="id">
                                <li class="rounded-xl border border-[#e4ece7] bg-[#fafcfb] p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="text-sm font-bold text-[#173b29]" x-text="(gejala.find(g => Number(g.id) === Number(id))?.nama) ?? 'Gejala #' + id"></span>
                                        <span class="rounded-full bg-[#e8f4ed] px-3 py-1 text-xs font-semibold text-[#176b45]" x-text="confLabel(id)"></span>
                                    </div>
                                    <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-5">
                                        <template x-for="lvl in levels" :key="lvl.value">
                                            <button type="button" @click="setConf(id, lvl.value)"
                                                    class="rounded-lg border px-2 py-2 text-center text-xs font-semibold transition-colors"
                                                    :class="confValue(id) === lvl.value
                                                        ? 'border-[#176b45] bg-[#176b45] text-white'
                                                        : 'border-[#dbe5df] bg-white text-[#66746c] hover:border-[#176b45]/40'">
                                                <span class="block" x-text="lvl.label"></span>
                                                <span class="mt-0.5 block text-[10px] font-normal opacity-70" x-text="lvl.value.toFixed(1)"></span>
                                            </button>
                                        </template>
                                    </div>
                                </li>
                            </template>
                        </ul>

                        <div class="mt-4 flex items-center justify-between border-t border-[#eef3ef] pt-4">
                            <span class="text-xs font-semibold text-[#8a9990]" x-text="selected.length + ' gejala · keyakinan dikonversi ke skala 0–1'"></span>
                            <div class="flex gap-2">
                                <button type="button" @click="prev()" class="rounded-xl border border-[#dbe5df] bg-white px-4 py-2 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">Kembali</button>
                                <button type="button" @click="next()" class="rounded-xl bg-[#176b45] px-4 py-2 text-sm font-semibold text-white hover:bg-[#173b29]">Lanjut</button>
                            </div>
                        </div>
                    </x-card>
                </section>

                {{-- STEP 4: Proses Diagnosis --}}
                <section x-show="step === 4" x-cloak>
                    <x-card>
                        <h2 class="text-base font-bold text-[#173b29]">Step 4 · Proses Diagnosis</h2>
                        <p class="mt-1 text-sm text-[#66746c]">Periksa kembali pilihan Anda sebelum menjalankan diagnosis.</p>

                        <div class="mt-4 overflow-hidden rounded-xl border border-[#e4ece7]">
                            <div class="flex items-center justify-between bg-[#fafcfb] px-4 py-3">
                                <span class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Komoditas</span>
                                <span class="text-sm font-bold text-[#173b29]" x-text="komoditasNama(commodityId)"></span>
                            </div>
                            <div class="border-t border-[#eef3ef]">
                                <div class="flex items-center justify-between bg-[#fafcfb] px-4 py-3">
                                    <span class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Gejala Terpilih</span>
                                    <span class="text-sm font-bold text-[#176b45]" x-text="selected.length + ' gejala'"></span>
                                </div>
                                <ul class="divide-y divide-[#eef3ef]">
                                    <template x-for="id in selected" :key="'sum' + id">
                                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                                            <span class="flex items-center gap-2 text-sm text-[#173b29]">
                                                <svg class="h-4 w-4 shrink-0 text-[#176b45]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                <span x-text="(gejala.find(g => Number(g.id) === Number(id))?.nama) ?? 'Gejala #' + id"></span>
                                            </span>
                                            <span class="rounded-full bg-[#e8f4ed] px-2.5 py-1 text-xs font-semibold text-[#176b45]" x-text="confLabel(id)"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-[#eef3ef] pt-4">
                            <button type="button" @click="prev()" class="rounded-xl border border-[#dbe5df] bg-white px-4 py-2 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">Kembali</button>
                            <button type="submit" :disabled="submitting || selected.length === 0"
                                    class="inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#173b29] disabled:cursor-not-allowed disabled:opacity-60">
                                <svg x-show="!submitting" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                <svg x-show="submitting" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                <span x-text="submitting ? 'Memproses…' : 'Proses Diagnosis'"></span>
                            </button>
                        </div>
                    </x-card>
                </section>
            </form>
        </div>
    @endif
@endsection