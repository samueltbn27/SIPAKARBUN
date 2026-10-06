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

        @if ($akunPoktanBermasalah)
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <p class="font-semibold">Akun Poktan Anda belum dapat mengajukan permohonan.</p>
                <p class="mt-0.5 text-xs">{{ $alasanBlokir === 'belum_terikat' ? 'Akun Anda belum terikat Kelompok Tani. Hubungi Admin untuk aktivasi akun.' : 'Data Kelompok Tani akun Anda tidak tersedia pada referensi Disbun. Hubungi Admin.' }}</p>
            </div>
        @endif

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
        @section('subtitle', 'Lengkapi data permohonan dan tentukan lokasi kasus yang memerlukan penanganan.')
        <x-page-header
            title="Ajukan Permohonan Penanganan"
            subtitle="Lengkapi data permohonan dan tentukan lokasi kasus yang memerlukan penanganan."
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Permohonan Saya', 'url' => route('permohonan.index')],
                ['label' => 'Ajukan Permohonan'],
            ]"
        />

        @if ($akunPoktanBermasalah)
            <x-card class="mx-auto max-w-xl p-8 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                </span>
                <h2 class="mt-4 text-lg font-bold text-[#173b29]">Permohonan belum dapat dibuat</h2>
                <p class="mt-1 text-sm text-[#66746c]">
                    @if ($alasanBlokir === 'belum_terikat')
                        Akun Poktan Anda belum terikat Kelompok Tani. Hubungi Admin untuk aktivasi akun sebelum mengajukan permohonan.
                    @else
                        Data Kelompok Tani akun Anda tidak tersedia pada referensi Disbun saat ini. Silakan coba lagi nanti atau hubungi Admin.
                    @endif
                </p>
                <a href="{{ route('permohonan.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">
                    Kembali ke Permohonan Saya
                </a>
            </x-card>
        @else
        <div x-data="{
            step: {{ $errors->any() ? 1 : $initialStep }},
            submitting: false,
            locationError: false,
            konfirmasiError: false,
            lokasiDikonfirmasi: {{ Js::from((bool) old('lokasi_dikonfirmasi')) }},
            latitudeKasus: {{ Js::from(old('latitude_kasus', $lokasiAwal['latitude'])) }},
            longitudeKasus: {{ Js::from(old('longitude_kasus', $lokasiAwal['longitude'])) }},
            alamatKasus: {{ Js::from(old('alamat_kasus')) }},
            catatanPemohon: {{ Js::from(old('catatan_pemohon')) }},
            files: [],
            onFiles(e) {
                this.files = Array.from(e.target.files || []);
            },
            fmtBytes(b) {
                if (! b) return '0 B';
                const u = ['B', 'KB', 'MB'];
                let i = 0;
                while (b >= 1024 && i < u.length - 1) { b /= 1024; i++; }
                return b.toFixed(b >= 10 || i === 0 ? 0 : 1) + ' ' + u[i];
            },
            next() {
                const hasLatitude = this.$refs.lat && this.$refs.lat.value.trim() !== '';
                const hasLongitude = this.$refs.lng && this.$refs.lng.value.trim() !== '';
                this.locationError = ! hasLatitude || ! hasLongitude;
                this.konfirmasiError = ! this.lokasiDikonfirmasi;
                if (this.locationError || this.konfirmasiError) return;
                this.step = 2;
            },
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
                <div x-show="step === 1" class="grid items-start grid-cols-1 gap-6 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
                    {{-- Data Diagnosis (readonly) --}}
                    <x-card class="lg:col-start-1 lg:row-start-1">
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

                    {{-- Kelompok Tani (otomatis dari akun, read-only) --}}
                    <x-card class="p-5 lg:col-start-1 lg:row-start-2">
                        <h3 class="mb-1 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Kelompok Tani</h3>
                        <p class="mb-3 text-xs text-[#8a9990]">Kelompok tani pengaju mengikuti akun Anda secara otomatis.</p>

                        <div class="rounded-xl bg-[#f3f8f4] p-3 text-xs text-[#66746c]">
                            <p class="font-semibold text-[#173b29]">{{ $poktanMilik['nama'] }}</p>
                            <p class="mt-0.5">Kode: <span class="font-semibold text-[#176b45]">{{ ($poktanMilik['kode_kelompok'] ?? null) ?: $poktanMilik['kode'] }}</span></p>
                            @if(!empty($poktanMilik['jenis_komoditi']))<p>Komoditas: {{ $poktanMilik['jenis_komoditi'] }}</p>@endif
                            @if(!empty($poktanMilik['kecamatan']) || !empty($poktanMilik['kabupaten']))
                                <p>Wilayah: {{ collect([$poktanMilik['kecamatan'] ?? null, $poktanMilik['kabupaten'] ?? null])->filter()->join(', ') }}</p>
                            @endif
                            <p class="mt-2 inline-flex items-center gap-1 rounded-full bg-[#e8f4ed] px-2 py-0.5 font-semibold text-[#176b45]">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Terkunci mengikuti akun Anda
                            </p>
                        </div>
                    </x-card>

                    {{-- Lokasi Kasus --}}
                    <x-card class="p-5 lg:col-start-2 lg:row-start-1 lg:row-span-2">
                        <h3 class="mb-1 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Lokasi Kasus</h3>
                        <p class="mb-3 text-xs text-[#8a9990]">
                            Lokasi kasus otomatis mengikuti lokasi Kelompok Tani Anda. Geser marker atau klik peta jika lokasi serangan berada di titik yang berbeda.
                        </p>
                        <div class="mb-4 overflow-hidden rounded-2xl border border-[#dbe5df] bg-[#f3f8f4]">
                            <div id="case-location-map" data-case-location-map class="h-[280px] w-full sm:h-[320px] lg:h-[380px]" aria-label="Peta untuk memilih lokasi kasus"></div>
                            <div class="flex items-center gap-2 border-t border-[#dbe5df] bg-white px-3 py-2.5 text-xs text-[#66746c]">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-[#176b45]"></span>
                                <span data-case-location-help>Lokasi kasus otomatis mengikuti lokasi Kelompok Tani Anda. Geser marker atau klik peta jika lokasi serangan berada di titik yang berbeda.</span>
                            </div>
                        </div>
                        <p data-case-location-status class="mb-3 hidden rounded-lg bg-[#e8f4ed] px-3 py-2 text-xs font-semibold text-[#176b45]" role="status">Lokasi kasus telah dipilih.</p>
                        <p x-show="locationError" x-cloak class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700" role="alert">Pilih lokasi kasus pada peta sebelum melanjutkan.</p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label for="latitude_kasus" class="mb-1 block text-xs font-bold uppercase tracking-wide text-[#8a9990]">Latitude</label>
                            <input type="number" name="latitude_kasus" id="latitude_kasus"
                                       x-ref="lat"
                                       x-model="latitudeKasus"
                                       value="{{ old('latitude_kasus', $lokasiAwal['latitude']) }}" step="any" min="-90" max="90"
                                       required readonly
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
                                       x-model="longitudeKasus"
                                       value="{{ old('longitude_kasus', $lokasiAwal['longitude']) }}" step="any" min="-180" max="180"
                                       required readonly
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
                                          x-model="alamatKasus"
                                          placeholder="Blok/Kebun, desa, kecamatan, atau keterangan titik serangan"
                                          class="w-full rounded-xl border border-[#dbe5df] bg-white px-3 py-2.5 text-sm text-[#173b29] placeholder:text-[#a0aba4] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">{{ old('alamat_kasus') }}</textarea>
                                @error('alamat_kasus')
                                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl bg-[#f3f8f4] p-3">
                            <input type="checkbox" name="lokasi_dikonfirmasi" id="lokasi_dikonfirmasi" value="1"
                                   x-model="lokasiDikonfirmasi"
                                   @checked(old('lokasi_dikonfirmasi'))
                                   class="mt-0.5 h-4 w-4 shrink-0 rounded border-[#dbe5df] text-[#176b45] focus:ring-2 focus:ring-[#176b45]/30">
                            <span class="text-xs leading-relaxed text-[#173b29]">
                                <span class="font-semibold">Saya menyatakan titik lokasi kasus di atas sudah benar.</span>
                                <span class="text-[#66746c]"> Centang setelah memastikan marker sesuai dengan lokasi serangan di lapangan.</span>
                            </span>
                        </label>
                        <p x-show="konfirmasiError" x-cloak class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700" role="alert">Centang konfirmasi lokasi sebelum melanjutkan.</p>
                        @error('lokasi_dikonfirmasi')
                            <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </x-card>

                    {{-- Catatan + Foto --}}
                    <x-card class="p-5 lg:col-start-1 lg:row-start-3">
                        <h3 class="mb-1 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Catatan Pemohon</h3>
                        <textarea name="catatan_pemohon" rows="4" maxlength="2000"
                                  x-ref="catatan"
                                  x-model="catatanPemohon"
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
                                <dd class="text-right font-semibold text-[#173b29]">{{ $poktanMilik['nama'] }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 border-t border-[#eef3ef] pt-4">
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-[#8a9990]">Lokasi Kasus</p>
                            <p class="text-sm text-[#66746c]">
                                <span x-show="latitudeKasus" x-text="'Latitude: ' + latitudeKasus"></span>
                                <span x-show="longitudeKasus" x-text="' · Longitude: ' + longitudeKasus"></span>
                            </p>
                            <p class="mt-1 text-sm text-[#66746c]" x-text="alamatKasus"></p>
                            <p class="mt-2 text-[11px] text-[#8a9990]">Lokasi kasus = titik serangan OPT di lapangan, terpisah dari lokasi kelompok tani.</p>
                            <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-[#e8f4ed] px-2.5 py-1 text-[11px] font-semibold text-[#176b45]">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span x-show="lokasiDikonfirmasi">Lokasi sudah saya konfirmasi</span>
                                <span x-show="! lokasiDikonfirmasi" class="text-red-600">Belum dikonfirmasi</span>
                            </p>
                        </div>

                        <div class="mt-4 border-t border-[#eef3ef] pt-4">
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-[#8a9990]">Catatan Pemohon</p>
                            <p class="whitespace-pre-line text-sm text-[#66746c]" x-text="catatanPemohon || '—'"></p>
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
                <div class="mt-6 flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <button type="button" x-show="step === 2" @click="prev()"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-[#dbe5df] bg-white px-5 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4] sm:w-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        Kembali
                    </button>
                    <button type="button" x-show="step === 1" @click="next()"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29] sm:w-auto">
                        Tinjau Permohonan
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                    <button type="submit" x-show="step === 2" :disabled="submitting"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29] disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
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
    @endif
@endsection
