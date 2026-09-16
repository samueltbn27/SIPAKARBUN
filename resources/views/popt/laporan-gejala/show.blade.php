@extends('layouts.app')

@section('title', 'Tinjau Laporan Gejala')

@section('content')
    <x-page-header
        title="Tinjau Laporan Gejala"
        subtitle="{{ $laporan->report_code }} · {{ $laporan->statusLabel() }}"
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('knowledge.dashboard')], ['label' => 'Laporan Gejala', 'url' => route('popt.laporan-gejala.index')], ['label' => $laporan->report_code]]"
    />

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><p class="font-semibold">Periksa kembali data tinjauan.</p><ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(320px,.9fr)]">
        <x-card class="space-y-5 p-5 sm:p-6">
            <div>
                <p class="font-mono text-xs font-bold text-[#176b45]">{{ $laporan->report_code }}</p>
                <h2 class="mt-1 text-lg font-bold text-[#173b29]">{{ $laporan->commodity_name_snapshot }}</h2>
                <p class="mt-1 text-xs text-[#8a9990]">Dilaporkan {{ $laporan->created_at?->format('d M Y, H:i') }} oleh {{ $laporan->reporter?->name ?? 'Akun tidak tersedia' }}</p>
            </div>
            <div><h3 class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Deskripsi gejala</h3><p class="mt-2 whitespace-pre-line text-sm leading-6 text-[#34483b]">{{ $laporan->description }}</p></div>
            @if ($laporan->location_description)<div><h3 class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Lokasi kebun</h3><p class="mt-2 whitespace-pre-line text-sm text-[#34483b]">{{ $laporan->location_description }}</p></div>@endif
            @if ($laporan->additional_information)<div class="rounded-xl bg-[#f5faf6] p-4"><h3 class="text-xs font-bold uppercase tracking-wide text-[#176b45]">Informasi tambahan dari Poktan</h3><p class="mt-2 whitespace-pre-line text-sm leading-6 text-[#34483b]">{{ $laporan->additional_information }}</p>@if($laporan->responded_at)<p class="mt-2 text-xs text-[#8a9990]">Diperbarui {{ $laporan->responded_at->format('d M Y, H:i') }}</p>@endif</div>@endif
            @if ($laporan->imageUrl())<div><h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-[#8a9990]">Foto bukti</h3><img src="{{ $laporan->imageUrl() }}" alt="Foto gejala {{ $laporan->report_code }}" class="max-h-[520px] w-full rounded-xl border border-[#e4ece7] bg-[#fafcfb] object-contain" loading="lazy"></div>@endif
            @if ($laporan->review_note)<div class="rounded-xl border border-amber-200 bg-amber-50 p-4"><h3 class="text-xs font-bold uppercase tracking-wide text-amber-900">Catatan tinjauan terakhir</h3><p class="mt-2 whitespace-pre-line text-sm leading-6 text-amber-900">{{ $laporan->review_note }}</p></div>@endif
            @if ($laporan->gejala)<div class="rounded-xl border border-green-200 bg-green-50 p-4"><p class="text-sm font-bold text-green-900">Draft gejala: {{ $laporan->gejala->nama }}</p><p class="mt-1 text-xs leading-5 text-green-800">Status {{ $laporan->gejala->status }}. Aktivasi hanya dapat dilakukan Admin atau Operator UPTD melalui Publikasi Knowledge.</p></div>@endif
        </x-card>

        @if ($laporan->status === \App\Models\LaporanGejala::STATUS_DIAJUKAN)
            <div class="space-y-5">
                <x-card class="p-5 sm:p-6">
                    <h2 class="text-base font-bold text-[#173b29]">Tindakan tinjauan</h2>
                    <p class="mt-1 text-xs leading-5 text-[#66746c]">Pilih satu tindakan. Gejala baru dibuat sebagai draft dan tidak langsung muncul pada Diagnosis.</p>

                    <form method="POST" action="{{ route('popt.laporan-gejala.review', $laporan) }}" class="mt-5 space-y-3">
                        @csrf
                        <input type="hidden" name="action" value="perlu_informasi">
                        <label for="info-note" class="block text-xs font-semibold text-[#66746c]">Catatan untuk meminta informasi</label>
                        <textarea id="info-note" name="review_note" rows="3" maxlength="2000" class="w-full rounded-xl border border-[#dbe5df] px-3 py-2.5 text-sm focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20" placeholder="Informasi apa yang perlu dilengkapi?">{{ old('review_note') }}</textarea>
                        <button type="submit" class="min-h-11 w-full rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-900 hover:bg-amber-100">Minta informasi tambahan</button>
                    </form>

                    <form method="POST" action="{{ route('popt.laporan-gejala.review', $laporan) }}" class="mt-5 space-y-3 border-t border-[#eef3ef] pt-5">
                        @csrf
                        <input type="hidden" name="action" value="duplikat">
                        <label for="duplicate-note" class="block text-xs font-semibold text-[#66746c]">Alasan duplikat</label>
                        <textarea id="duplicate-note" name="review_note" rows="2" maxlength="2000" class="w-full rounded-xl border border-[#dbe5df] px-3 py-2.5 text-sm focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20" placeholder="Sebutkan gejala atau laporan yang serupa.">{{ old('review_note') }}</textarea>
                        <button type="submit" class="min-h-11 w-full rounded-xl border border-[#dbe5df] bg-white px-4 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">Tandai sebagai duplikat</button>
                    </form>

                    <form method="POST" action="{{ route('popt.laporan-gejala.review', $laporan) }}" class="mt-5 space-y-3 border-t border-[#eef3ef] pt-5">
                        @csrf
                        <input type="hidden" name="action" value="buat_draft">
                        <label for="draft_symptom_name" class="block text-xs font-semibold text-[#66746c]">Nama gejala untuk draft</label>
                        <input id="draft_symptom_name" name="draft_symptom_name" value="{{ old('draft_symptom_name') }}" maxlength="150" class="w-full rounded-xl border border-[#dbe5df] px-3 py-2.5 text-sm focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20" placeholder="Contoh: Daun menggulung disertai bercak putih">
                        <p class="text-xs leading-5 text-[#8a9990]">Deskripsi dan foto laporan akan disalin ke draft gejala. Admin/Operator tetap perlu meninjau dan memublikasikannya.</p>
                        <button type="submit" class="min-h-11 w-full rounded-xl bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">Buat draft gejala</button>
                    </form>
                </x-card>
            </div>
        @endif

        <a href="{{ route('popt.laporan-gejala.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-[#dbe5df] bg-white px-4 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4] lg:col-start-2">Kembali ke antrean</a>
    </div>
@endsection
