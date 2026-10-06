@extends('layouts.app')

@section('title', 'Review Kajian POPT')

@section('content')
    <x-page-header
        title="Review Kajian POPT"
        subtitle="{{ $laporan->report_code }} · {{ $laporan->statusLabel() }}"
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('knowledge.dashboard')], ['label' => 'Review Gejala Baru', 'url' => route('operator.laporan-gejala.index')], ['label' => $laporan->report_code]]"
    />

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><p class="font-semibold">Periksa kembali keputusan Anda.</p><ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(320px,.9fr)]">
        <x-card class="space-y-5 p-5 sm:p-6">
            <div>
                <p class="font-mono text-xs font-bold text-[#176b45]">{{ $laporan->report_code }}</p>
                <h2 class="mt-1 text-lg font-bold text-[#173b29]">{{ $laporan->commodity_name_snapshot }}</h2>
                <p class="mt-1 text-xs text-[#8a9990]">Dilaporkan {{ $laporan->created_at?->format('d M Y, H:i') }} oleh {{ $laporan->reporter?->name ?? 'Akun tidak tersedia' }}</p>
            </div>
            <div><h3 class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Deskripsi gejala (Poktan)</h3><p class="mt-2 whitespace-pre-line text-sm leading-6 text-[#34483b]">{{ $laporan->description }}</p></div>
            @if ($laporan->location_description)<div><h3 class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Lokasi kebun</h3><p class="mt-2 whitespace-pre-line text-sm text-[#34483b]">{{ $laporan->location_description }}</p></div>@endif
            @if ($laporan->additional_information)<div class="rounded-xl bg-[#f5faf6] p-4"><h3 class="text-xs font-bold uppercase tracking-wide text-[#176b45]">Informasi tambahan dari Poktan</h3><p class="mt-2 whitespace-pre-line text-sm leading-6 text-[#34483b]">{{ $laporan->additional_information }}</p></div>@endif
            <div class="rounded-xl border border-[#e4ece7] p-4">
                <h3 class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Kajian POPT</h3>
                <p class="mt-2 text-sm text-[#34483b]">Pengkaji: <span class="font-semibold text-[#173b29]">{{ $laporan->reviewer?->name ?? '—' }}</span> · {{ $laporan->reviewed_at?->format('d M Y, H:i') ?? '—' }}</p>
                @if ($laporan->review_note)<p class="mt-2 whitespace-pre-line text-sm leading-6 text-[#34483b]">{{ $laporan->review_note }}</p>@endif
                @if ($laporan->gejala)
                    <p class="mt-3 text-sm font-bold text-[#173b29]">Draft gejala: {{ $laporan->gejala->nama }}</p>
                    <p class="mt-1 text-xs text-[#8a9990]">Status draft: {{ $laporan->gejala->status }} · Kriteria observasi: {{ $laporan->gejala->kriteria_observasi ? 'terisi' : 'belum diisi' }} · Metode pengamatan: {{ $laporan->gejala->metode_pengamatan ? 'terisi' : 'belum diisi' }}</p>
                    <a href="{{ route('knowledge.gejala.show', $laporan->gejala) }}" class="mt-2 inline-block text-xs font-semibold text-[#176b45] hover:underline">Buka draft gejala →</a>
                @endif
            </div>
            @if ($laporan->diagnosis)
                <p class="rounded-xl bg-[#f3f8f4] p-3 text-xs text-[#66746c]">Dilaporkan bersamaan dengan diagnosis <a href="{{ route('diagnosis.show', $laporan->diagnosis) }}" class="font-bold text-[#176b45] hover:underline">{{ $laporan->diagnosis->kode }}</a>.</p>
            @endif
        </x-card>

        <div class="space-y-5">
            @if ($laporan->operator_review !== null)
                <x-card class="p-5 sm:p-6">
                    <h2 class="text-base font-bold text-[#173b29]">Hasil review</h2>
                    <p class="mt-2">
                        @if ($laporan->operator_review === \App\Models\LaporanGejala::REVIEW_SETUJU)
                            <span class="inline-flex rounded-full bg-[#e8f4ed] px-3 py-1 text-xs font-semibold text-[#176b45]">Disetujui Operator</span>
                        @else
                            <span class="inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">Ditolak Operator</span>
                        @endif
                    </p>
                    @if ($laporan->operator_review_note)<p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#34483b]">{{ $laporan->operator_review_note }}</p>@endif
                    <p class="mt-3 text-xs text-[#8a9990]">Oleh {{ $laporan->operatorReviewer?->name ?? '—' }} · {{ $laporan->operator_reviewed_at?->format('d M Y, H:i') ?? '—' }}</p>
                    @if ($laporan->operator_review === \App\Models\LaporanGejala::REVIEW_SETUJU && $laporan->gejala)
                        <a href="{{ route('knowledge.aturan-cf.create', ['gejala_id' => $laporan->gejala->id]) }}" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">Buat relasi penyakit & CF</a>
                    @endif
                </x-card>
            @else
                <x-card class="p-5 sm:p-6">
                    <h2 class="text-base font-bold text-[#173b29]">Keputusan review</h2>
                    <p class="mt-1 text-xs leading-5 text-[#66746c]">Persetujuan membuka pembuatan relasi CF untuk draft gejala ini. Penolakan menghentikan rantai (tidak bisa direlasikan maupun dipublish).</p>

                    <form method="POST" action="{{ route('operator.laporan-gejala.review', $laporan) }}" class="mt-5 space-y-3">
                        @csrf
                        <input type="hidden" name="keputusan" value="setuju">
                        <label for="catatan-setuju" class="block text-xs font-semibold text-[#66746c]">Catatan persetujuan (opsional)</label>
                        <textarea id="catatan-setuju" name="catatan" rows="2" maxlength="2000" class="w-full rounded-xl border border-[#dbe5df] px-3 py-2.5 text-sm focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20" placeholder="Catatan untuk POPT (opsional).">{{ old('catatan') }}</textarea>
                        <button type="submit" class="min-h-11 w-full rounded-xl bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">Setujui kajian</button>
                    </form>

                    <form method="POST" action="{{ route('operator.laporan-gejala.review', $laporan) }}" class="mt-5 space-y-3 border-t border-[#eef3ef] pt-5">
                        @csrf
                        <input type="hidden" name="keputusan" value="ditolak">
                        <label for="catatan-tolak" class="block text-xs font-semibold text-[#66746c]">Alasan penolakan (wajib)</label>
                        <textarea id="catatan-tolak" name="catatan" rows="3" maxlength="2000" required class="w-full rounded-xl border border-[#dbe5df] px-3 py-2.5 text-sm focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20" placeholder="Jelaskan mengapa kajian ini ditolak.">{{ old('catatan') }}</textarea>
                        <button type="submit" class="min-h-11 w-full rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Tolak kajian</button>
                    </form>
                </x-card>
            @endif
        </div>

        <a href="{{ route('operator.laporan-gejala.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-[#dbe5df] bg-white px-4 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4] lg:col-start-2">Kembali ke antrean</a>
    </div>
@endsection
