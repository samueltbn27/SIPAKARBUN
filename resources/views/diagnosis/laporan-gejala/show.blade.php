@extends('layouts.app')

@section('title', 'Detail Laporan Gejala')

@section('content')
    <x-page-header
        title="Detail Laporan Gejala"
        subtitle="Laporan {{ $laporan->report_code }}"
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Laporan Gejala Saya', 'url' => route('diagnosis.reports.index')], ['label' => $laporan->report_code]]"
    />

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="status">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(280px,.8fr)]">
        <x-card class="space-y-5 p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-mono text-xs font-bold text-[#176b45]">{{ $laporan->report_code }}</p>
                    <h2 class="mt-1 text-lg font-bold text-[#173b29]">{{ $laporan->commodity_name_snapshot }}</h2>
                </div>
                <span class="rounded-full bg-[#eef3ef] px-3 py-1.5 text-xs font-semibold text-[#53645a]">{{ $laporan->statusLabel() }}</span>
            </div>

            <div>
                <h3 class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Gejala yang diamati</h3>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-[#34483b]">{{ $laporan->description }}</p>
            </div>

            @if ($laporan->location_description)
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Lokasi / keterangan kebun</h3>
                    <p class="mt-2 whitespace-pre-line text-sm text-[#34483b]">{{ $laporan->location_description }}</p>
                </div>
            @endif

            @if ($laporan->imageUrl())
                <div>
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-[#8a9990]">Foto bukti</h3>
                    <img src="{{ $laporan->imageUrl() }}" alt="Foto gejala pada laporan {{ $laporan->report_code }}" class="max-h-[420px] w-full rounded-xl border border-[#e4ece7] bg-[#fafcfb] object-contain" loading="lazy">
                </div>
            @endif

            <p class="border-t border-[#eef3ef] pt-4 text-xs text-[#8a9990]">Dikirim {{ $laporan->created_at?->format('d M Y, H:i') }}</p>
        </x-card>

        <div class="space-y-5">
            @if ($laporan->review_note)
                <x-card class="border-amber-200 bg-amber-50 p-5">
                    <h2 class="text-sm font-bold text-amber-900">Catatan dari POPT</h2>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-amber-900">{{ $laporan->review_note }}</p>
                    @if ($laporan->reviewed_at)<p class="mt-3 text-xs text-amber-800">Ditinjau {{ $laporan->reviewed_at->format('d M Y, H:i') }}</p>@endif
                </x-card>
            @endif

            @if ($laporan->additional_information)
                <x-card class="p-5">
                    <h2 class="text-sm font-bold text-[#173b29]">Informasi tambahan yang dikirim</h2>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-[#66746c]">{{ $laporan->additional_information }}</p>
                </x-card>
            @endif

            @if ($laporan->status === \App\Models\LaporanGejala::STATUS_PERLU_INFORMASI)
                <x-card class="p-5">
                    <h2 class="text-sm font-bold text-[#173b29]">Lengkapi informasi</h2>
                    <p class="mt-1 text-xs leading-5 text-[#66746c]">Setelah dikirim, laporan akan kembali ke antrean tinjauan POPT.</p>
                    <form method="POST" action="{{ route('diagnosis.reports.respond', $laporan) }}" class="mt-4 space-y-3">
                        @csrf
                        <label for="additional_information" class="block text-xs font-semibold text-[#66746c]">Informasi tambahan</label>
                        <textarea id="additional_information" name="additional_information" rows="4" maxlength="2000" required class="w-full rounded-xl border border-[#dbe5df] px-3 py-2.5 text-sm focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20" placeholder="Contoh: gejala mulai terlihat sejak tiga hari lalu.">{{ old('additional_information') }}</textarea>
                        @error('additional_information')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        <button type="submit" class="min-h-11 w-full rounded-xl bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">Kirim informasi ke POPT</button>
                    </form>
                </x-card>
            @elseif ($laporan->status === \App\Models\LaporanGejala::STATUS_DRAFT_DIBUAT)
                <x-card class="border-green-200 bg-green-50 p-5">
                    <h2 class="text-sm font-bold text-green-900">Draft gejala sudah dibuat</h2>
                    <p class="mt-2 text-sm leading-6 text-green-800">Draft masih menunggu pemeriksaan dan publikasi Admin atau Operator UPTD. Gejala ini belum digunakan dalam Diagnosis.</p>
                </x-card>
            @elseif ($laporan->status === \App\Models\LaporanGejala::STATUS_DUPLIKAT)
                <x-card class="p-5">
                    <h2 class="text-sm font-bold text-[#173b29]">Laporan ditandai sebagai duplikat</h2>
                    <p class="mt-2 text-sm leading-6 text-[#66746c]">Silakan baca catatan POPT untuk penjelasan lebih lanjut.</p>
                </x-card>
            @endif

            <a href="{{ route('diagnosis.reports.index') }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-[#dbe5df] bg-white px-4 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">Kembali ke laporan saya</a>
        </div>
    </div>
@endsection
