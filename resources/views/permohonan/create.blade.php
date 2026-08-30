@extends('layouts.app')

@section('title', 'Ajukan Permohonan Penanganan')

@php
    $primary = $selectedDiagnosis?->results?->first();
    $komoditasNama = $selectedDiagnosis === null
        ? null
        : ($komoditas['nama'] ?? ('Komoditas #'.$selectedDiagnosis->commodity_id));
@endphp

@section('content')
    @if ($selectedDiagnosis === null)
        {{-- ===== Pilih Diagnosis ===== --}}
        <x-page-header
            title="Ajukan Permohonan Penanganan"
            subtitle="Pilih hasil diagnosis yang ingin Anda ajukan untuk penanganan."
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Permohonan Saya', 'url' => route('permohonan.index')],
                ['label' => 'Ajukan Permohonan'],
            ]"
        />

        @if ($diagnoses->isEmpty())
            <x-card class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#e8f4ed] text-[#176b45]">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </span>
                <h2 class="mt-4 text-lg font-bold text-[#173b29]">Belum ada diagnosis untuk diajukan</h2>
                <p class="mt-1 max-w-md text-sm text-[#66746c]">Jalankan diagnosis tanaman terlebih dahulu sebelum mengajukan permohonan penanganan.</p>
                <a href="{{ route('diagnosis.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0 0l-4-4m4 4l4-4" /></svg>
                    Diagnosis Sekarang
                </a>
            </x-card>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($diagnoses as $diagnosis)
                    @php
                        $top = $diagnosis->results->first();
                        $komoditasNamaDiag = $komoditasMap[$diagnosis->commodity_id] ?? ('Komoditas #'.$diagnosis->commodity_id);
                    @endphp
                    <x-card class="flex flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-[#176b45]">{{ $diagnosis->kode }}</p>
                                <p class="mt-0.5 text-sm font-semibold text-[#173b29]">{{ $komoditasNamaDiag }}</p>
                            </div>
                            <span class="rounded-full bg-[#e8f4ed] px-2.5 py-1 text-xs font-semibold text-[#176b45]">{{ number_format((float) ($top?->cf_value ?? 0), 2, ',', '.') }}</span>
                        </div>
                        <p class="mt-3 text-sm text-[#66746c]">
                            @if ($top === null)
                                Tidak ada hasil
                            @else
                                {{ $top->disease_name_snapshot }}
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-[#8a9990]">{{ $diagnosis->created_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y · H:i') ?? '—' }}</p>
                        <a href="{{ route('permohonan.create', ['diagnosis_id' => $diagnosis->id]) }}"
                           class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">
                            Ajukan Penanganan
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    </x-card>
                @endforeach
            </div>
        @endif
    @else
        {{-- ===== Form Permohonan (wizard: Isi → Review) ===== --}}
        <x-page-header
            title="Ajukan Permohonan Penanganan"
            subtitle="Lengkapi data permohonan untuk diagnosis {{ $selectedDiagnosis->kode }}, lalu tinjau sebelum mengirim."
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Permohonan Saya', 'url' => route('permohonan.index')],
                ['label' => 'Ajukan Permohonan'],
            ]"
        />

        <div x-data="{
            step: {{ $initialStep }},
            submitting: false,
            kelompokTaniList: {{ Js::from($kelompokTaniList) }},
            kelompokTaniId: {{ Js::from(old('kelompok_tani_id')) }},
            kelompokTaniQuery: '',
            kelompokTaniLoading: false,
            kelompokTaniSearchError: false,
            async cariKelompokTani() {
                const query = this.kelompokTaniQuery.trim();
                this.kelompokTaniLoading = true;
                this.kelompokTaniSearchError = false;
                try {
                    const params = new URLSearchParams({ q: query });
                    if (this.kelompokTaniId) params.set('selected', this.kelompokTaniId);
                    const response = await fetch('{{ route('references.kelompok-tani') }}?' + params.toString(), {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (! response.ok) throw new Error('reference-search-failed');
                    const payload = await response.json();
                    this.kelompokTaniList = Array.isArray(payload.data) ? payload.data : [];
                } catch (error) {
                    this.kelompokTaniList = [];
                    this.kelompokTaniSearchError = true;
                } finally {
                    this.kelompokTaniLoading = false;
                }
            },
            files: [],
            onFiles(e) {
                this.files = Array.from(e.target.files || []);
            },
            get kelompokTaniTerpilih() {
                return this.kelompokTaniList.find((k) => String(k.id) === String(this.kelompokTaniId)) || null;
            },
            get kelompokTaniHasil() {
                const query = this.kelompokTaniQuery.trim().toLowerCase();
                if (! query) return this.kelompokTaniList;

                return this.kelompokTaniList.filter((k) => [k.nama, k.kode, k.kode_kelompok, k.kabupaten, k.kecamatan, k.kelurahan, k.desa]
                    .filter(Boolean)
                    .some((value) => String(value).toLowerCase().includes(query)));
            },
            fmtBytes(b) {
                if (! b) return '0 B';
                const u = ['B', 'KB', 'MB'];
                let i = 0;
                while (b >= 1024 && i < u.length - 1) { b /= 1024; i++; }
                return b.toFixed(b >= 10 || i === 0 ? 0 : 1) + ' ' + u[i];
            },
            next() { this.step = 2; },
            prev() { this.step = 1; },
        }">
            <div class="mb-6 flex items-center gap-2">
                <template x-for="(label, i) in ['Isi Form', 'Review &amp; Kirim']" :key="i">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
                              :class="step > i ? 'bg-[#176b45] text-white' : step === i + 1 ? 'bg-[#176b45] text-white ring-4 ring-[#176b45]/20' : 'bg-[#eef3ef] text-[#8a9990]'"
                              x-text="i + 1"></span>
                        <span class="text-sm font-semibold" :class="step === i + 1 ? 'text-[#173b29]' : 'text-[#8a9990]'" x-text="label"></span>
                        <template x-if="i < 1">
                            <svg class="h-4 w-4 text-[#b9c4bd]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </template>
                    </div>
                </template>
            </div>

            <form method="POST" action="{{ route('permohonan.store') }}"
                  enctype="multipart/form-data" @submit="submitting = true">
                @csrf
                <input type="hidden" name="diagnosis_id" value="{{ $selectedDiagnosis->id }}">

                {{-- ===== Step 1: Isi Form ===== --}}
                <div x-show="step === 1" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {{-- Data Diagnosis (readonly) --}}
                    <x-card class="lg:row-span-2">
                        <div class="border-b border-[#eef3ef] px-5 py-4">
                            <h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Data Diagnosis</h3>
                        </div>
                        <dl class="divide-y divide-[#eef3ef] text-sm">
                            <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                                <dt class="text-[#8a9990]">Kode Diagnosis</dt>
                                <dd class="font-bold text-[#176b45]">{{ $selectedDiagnosis->kode }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                                <dt class="text-[#8a9990]">Komoditas</dt>
                                <dd class="text-right font-semibold text-[#173b29]">{{ $komoditasNama }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                                <dt class="text-[#8a9990]">Penyakit Utama</dt>
                                <dd class="text-right font-semibold text-[#173b29]">{{ $primary?->disease_name_snapshot ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                                <dt class="text-[#8a9990]">Nilai CF</dt>
                                <dd class="font-bold text-[#176b45]">{{ $primary === null ? '—' : number_format((float) $primary->cf_value, 2, ',', '.') }}</dd>
                            </div>
                            <div class="px-5 py-4">
                                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-[#8a9990]">Gejala Dipilih</p>
                                <ul class="space-y-1.5">
                                    @forelse ($selectedDiagnosis->symptoms as $symptom)
                                        <li class="flex items-center gap-2 text-sm text-[#66746c]">
                                            <svg class="h-4 w-4 shrink-0 text-[#176b45]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            {{ $symptom->symptom_name_snapshot }}
                                            <span class="ml-auto shrink-0 rounded-full bg-[#eef3ef] px-2 py-0.5 text-[11px] font-semibold text-[#66746c]">{{ round(max(0.0, (float) $symptom->cf_user) * 100, 0) }}%</span>
                                        </li>
                                    @empty
                                        <li class="text-sm text-[#8a9990]">—</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="px-5 py-4">
                                <a href="{{ route('diagnosis.show', $selectedDiagnosis->id) }}"
                                   class="text-xs font-semibold text-[#176b45] hover:underline">Lihat detail diagnosis →</a>
                            </div>
                        </dl>
                    </x-card>

                    {{-- Kelompok Tani --}}
                    <x-card class="p-5">
                        <h3 class="mb-1 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Kelompok Tani</h3>
                        <p class="mb-3 text-xs text-[#8a9990]">Kelompok tani yang mengajukan permohonan ini.</p>

                        @if ($kelompokTaniError)
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700">
                                <p class="font-semibold">Data Kelompok Tani belum tersedia.</p>
                                <p class="mt-1">Referensi kelompok tani tidak dapat dimuat. Silakan lakukan sinkronisasi data Disbun lalu coba kembali.</p>
                            </div>
                        @else
                            <label for="kelompok-tani-search" class="sr-only">Cari kelompok tani</label>
                            <input type="search" id="kelompok-tani-search" x-model="kelompokTaniQuery"
                                   @input.debounce.300ms="cariKelompokTani()"
                                   placeholder="Cari nama, kode, kabupaten, kecamatan, atau kelurahan..."
                                   class="mb-2 w-full rounded-xl border border-[#dbe5df] bg-white px-3 py-2.5 text-sm text-[#173b29] placeholder:text-[#a0aba4] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">
                            <select name="kelompok_tani_id" id="kelompok-tani" x-model="kelompokTaniId" required
                                    class="w-full rounded-xl border border-[#dbe5df] bg-white px-3 py-2.5 text-sm text-[#173b29] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">
                                <option value="">Pilih Kelompok Tani</option>
                                <option value="" disabled x-show="kelompokTaniLoading">Memuat opsi...</option>
                                <template x-for="k in kelompokTaniHasil" :key="k.id">
                                    <option :value="String(k.id)" x-text="[k.nama, k.jenis_komoditi, [k.kecamatan, k.kabupaten].filter(Boolean).join(' · ')].filter(Boolean).join(' — ')"></option>
                                </template>
                            </select>
                            <p x-show="kelompokTaniLoading" class="mt-2 text-xs text-[#66746c]">Mencari data kelompok tani...</p>
                            <p x-show="!kelompokTaniLoading && kelompokTaniSearchError" class="mt-2 text-xs text-red-600">Data Kelompok Tani tidak tersedia. Sinkronisasi data Disbun mungkin gagal.</p>
                            <p x-show="!kelompokTaniLoading && !kelompokTaniSearchError && kelompokTaniHasil.length === 0" class="mt-2 text-xs text-amber-700">Data tidak tersedia untuk pencarian ini.</p>
                            @error('kelompok_tani_id')
                                <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror

                            <template x-if="kelompokTaniTerpilih">
                                <div class="mt-3 rounded-xl bg-[#f3f8f4] p-3 text-xs text-[#66746c]">
                                    <p class="font-semibold text-[#173b29]" x-text="kelompokTaniTerpilih.nama"></p>
                                    <p class="mt-0.5">Kode: <span class="font-semibold text-[#176b45]" x-text="kelompokTaniTerpilih.kode"></span></p>
                                    <p x-show="kelompokTaniTerpilih.jenis_komoditi">Komoditas: <span x-text="kelompokTaniTerpilih.jenis_komoditi"></span></p>
                                    <p x-show="kelompokTaniTerpilih.kecamatan || kelompokTaniTerpilih.kabupaten">Wilayah: <span x-text="[kelompokTaniTerpilih.kecamatan, kelompokTaniTerpilih.kabupaten].filter(Boolean).join(', ')"></span></p>
                                </div>
                            </template>
                        @endif
                    </x-card>

                    {{-- Lokasi Kasus --}}
                    <x-card class="p-5">
                        <h3 class="mb-1 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Lokasi Kasus</h3>
                        <p class="mb-3 text-xs text-[#8a9990]">
                            Lokasi kasus adalah titik lokasi serangan OPT di lapangan, <span class="font-semibold text-[#66746c]">terpisah dari lokasi kelompok tani</span>.
                            Koordinat kelompok tani tidak dipakai otomatis.
                        </p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label for="latitude_kasus" class="mb-1 block text-xs font-bold uppercase tracking-wide text-[#8a9990]">Latitude</label>
                                <input type="number" name="latitude_kasus" id="latitude_kasus"
                                       x-ref="lat"
                                       value="{{ old('latitude_kasus') }}" step="any" min="-90" max="90"
                                       placeholder="-6.9126"
                                       class="w-full rounded-xl border border-[#dbe5df] bg-white px-3 py-2.5 text-sm text-[#173b29] placeholder:text-[#a0aba4] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">
                                @error('latitude_kasus')
                                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="longitude_kasus" class="mb-1 block text-xs font-bold uppercase tracking-wide text-[#8a9990]">Longitude</label>
                                <input type="number" name="longitude_kasus" id="longitude_kasus"
                                       x-ref="lng"
                                       value="{{ old('longitude_kasus') }}" step="any" min="-180" max="180"
                                       placeholder="107.6085"
                                       class="w-full rounded-xl border border-[#dbe5df] bg-white px-3 py-2.5 text-sm text-[#173b29] placeholder:text-[#a0aba4] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">
                                @error('longitude_kasus')
                                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="alamat_kasus" class="mb-1 block text-xs font-bold uppercase tracking-wide text-[#8a9990]">Alamat / Keterangan Lokasi</label>
                                <textarea name="alamat_kasus" id="alamat_kasus" rows="3" maxlength="500"
                                          x-ref="alamat"
                                          placeholder="Blok/Kebun, desa, kecamatan, atau keterangan titik serangan"
                                          class="w-full rounded-xl border border-[#dbe5df] bg-white px-3 py-2.5 text-sm text-[#173b29] placeholder:text-[#a0aba4] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">{{ old('alamat_kasus') }}</textarea>
                                @error('alamat_kasus')
                                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </x-card>

                    {{-- Catatan + Foto --}}
                    <x-card class="p-5">
                        <h3 class="mb-1 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Catatan Pemohon</h3>
                        <textarea name="catatan_pemohon" rows="4" maxlength="2000"
                                  x-ref="catatan"
                                  placeholder="Deskripsi kondisi tanaman, luas terdampak, atau informasi tambahan (maks. 2.000 karakter)"
                                  class="mt-3 w-full rounded-xl border border-[#dbe5df] bg-white px-3 py-2.5 text-sm text-[#173b29] placeholder:text-[#a0aba4] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">{{ old('catatan_pemohon') }}</textarea>
                        @error('catatan_pemohon')
                            <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror

                        <h3 class="mt-5 mb-1 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Foto / Bukti</h3>
                        <p class="mb-3 text-xs text-[#8a9990]">Maksimal 5 file · JPG/PNG/WebP · masing-masing ≤ 5 MB.</p>
                        <input type="file" name="evidences[]" id="evidences" multiple
                               accept="image/jpeg,image/png,image/webp"
                               @change="onFiles($event)"
                               class="block w-full cursor-pointer rounded-xl border border-dashed border-[#dbe5df] bg-[#fafcfb] px-3 py-4 text-sm text-[#66746c] file:mr-3 file:rounded-lg file:border-0 file:bg-[#176b45] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-[#173b29]">
                        @error('evidences')
                            <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                        @error('evidences.*')
                            <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror

                        <ul class="mt-3 space-y-1" x-show="files.length">
                            <template x-for="(f, i) in files" :key="i">
                                <li class="flex items-center justify-between gap-2 rounded-lg bg-[#f3f8f4] px-3 py-2 text-xs text-[#66746c]">
                                    <span class="truncate font-medium text-[#173b29]" x-text="f.name"></span>
                                    <span class="shrink-0" x-text="fmtBytes(f.size)"></span>
                                </li>
                            </template>
                        </ul>
                        <p class="mt-2 text-xs text-[#8a9990]">Validasi mengikuti backend: tipe JPG/PNG/WebP, maksimal 5 MB per file, dan paling banyak 5 file.</p>
                    </x-card>
                </div>

                {{-- ===== Step 2: Review ===== --}}
                <div x-show="step === 2" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <x-card class="p-5">
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Ringkasan Permohonan</h3>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-[#8a9990]">Diagnosis</dt>
                                <dd class="font-bold text-[#173b29]">{{ $selectedDiagnosis->kode }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[#8a9990]">Komoditas</dt>
                                <dd class="text-right font-semibold text-[#173b29]">{{ $komoditasNama }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[#8a9990]">Penyakit Utama</dt>
                                <dd class="text-right font-semibold text-[#173b29]">{{ $primary?->disease_name_snapshot ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[#8a9990]">Nilai CF</dt>
                                <dd class="text-right font-bold text-[#176b45]">{{ $primary === null ? '—' : number_format((float) $primary->cf_value, 2, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="mb-1.5 text-[#8a9990]">Gejala Dipilih</dt>
                                <dd class="space-y-1.5">
                                    @forelse ($selectedDiagnosis->symptoms as $symptom)
                                        <p class="flex items-center gap-2 text-sm text-[#66746c]">
                                            <svg class="h-4 w-4 shrink-0 text-[#176b45]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            {{ $symptom->symptom_name_snapshot }}
                                        </p>
                                    @empty
                                        <p class="text-sm text-[#8a9990]">—</p>
                                    @endforelse
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[#8a9990]">Kelompok Tani</dt>
                                <dd class="text-right font-semibold text-[#173b29]">
                                    <template x-if="kelompokTaniTerpilih"><span x-text="kelompokTaniTerpilih.nama"></span></template>
                                    <template x-if="! kelompokTaniTerpilih"><span class="text-red-600">Belum dipilih</span></template>
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-4 border-t border-[#eef3ef] pt-4">
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-[#8a9990]">Lokasi Kasus</p>
                            <p class="text-sm text-[#66746c]">
                                <span x-show="$refs.lat && $refs.lat.value" x-text="'Latitude: ' + $refs.lat.value"></span>
                                <span x-show="$refs.lng && $refs.lng.value" x-text="' · Longitude: ' + $refs.lng.value"></span>
                            </p>
                            <p class="mt-1 text-sm text-[#66746c]" x-text="$refs.alamat ? $refs.alamat.value : ''"></p>
                            <p class="mt-2 text-[11px] text-[#8a9990]">Lokasi kasus = titik serangan OPT di lapangan, terpisah dari lokasi kelompok tani.</p>
                        </div>

                        <div class="mt-4 border-t border-[#eef3ef] pt-4">
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-[#8a9990]">Catatan Pemohon</p>
                            <p class="whitespace-pre-line text-sm text-[#66746c]" x-text="$refs.catatan ? ($refs.catatan.value || '—') : '—'"></p>
                        </div>

                        <div class="mt-4 border-t border-[#eef3ef] pt-4">
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-[#8a9990]">Foto / Bukti</p>
                            <p class="text-sm text-[#66746c]" x-show="! files.length">Tidak ada file.</p>
                            <ul class="space-y-1" x-show="files.length">
                                <template x-for="(f, i) in files" :key="i">
                                    <li class="text-xs text-[#66746c]" x-text="(i + 1) + '. ' + f.name + ' (' + fmtBytes(f.size) + ')'"></li>
                                </template>
                            </ul>
                        </div>
                    </x-card>

                    <x-card class="flex flex-col justify-between p-5">
                        <div>
                            <h3 class="mb-2 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Siap untuk dikirim?</h3>
                            <p class="text-sm leading-relaxed text-[#66746c]">
                                Setelah dikirim, permohonan berstatus <span class="font-semibold text-[#176b45]">Diajukan</span> dan akan direview oleh Operator UPTD.
                                Pastikan seluruh data sudah benar.
                            </p>
                            <ul class="mt-4 space-y-2 text-sm text-[#66746c]">
                                <li class="flex items-center gap-2">
                                    <svg class="h-4 w-4 shrink-0 text-[#176b45]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Diagnosis milik Anda sendiri
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="h-4 w-4 shrink-0 text-[#176b45]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Lokasi kasus terpisah dari lokasi kelompok tani
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="h-4 w-4 shrink-0 text-[#176b45]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Status awal: Diajukan
                                </li>
                            </ul>
                        </div>
                    </x-card>
                </div>

                {{-- Navigasi wizard --}}
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <button type="button" x-show="step === 2" @click="prev()"
                            class="inline-flex items-center gap-2 rounded-xl border border-[#dbe5df] bg-white px-5 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        Kembali
                    </button>
                    <button type="button" x-show="step === 1" @click="next()"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">
                        Tinjau Permohonan
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                    <button type="submit" x-show="step === 2" :disabled="submitting"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29] disabled:cursor-not-allowed disabled:opacity-60">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        <span x-show="! submitting">Kirim Permohonan</span>
                        <span x-show="submitting" class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            Mengirim…
                        </span>
                    </button>
                </div>
            </form>
        </div>
    @endif
@endsection
