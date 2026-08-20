@extends('layouts.app')

@section('title', 'Dashboard Operator')
@section('subtitle', 'Validasi pengajuan kasus & monitoring')

@section('content')
<div class="max-w-[1500px] mx-auto space-y-7">
    {{-- Header — template sama dengan dashboard POPT --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div><div class="flex items-center gap-2 text-xs text-[#8c9890] mb-2"><span>Operator</span><span>/</span><span class="text-[#176b45]">Dashboard</span></div><h1 class="text-2xl sm:text-[28px] font-bold tracking-tight text-[#173b29]">Selamat datang, {{ auth()->user()?->name }} <span class="text-xl">👋</span></h1><p class="mt-1 text-sm text-[#77847c]">Pantau pengajuan kasus, validasi pengajuan, dan status penanganan.</p></div>
        <a href="{{ route('knowledge.op.pengajuan-masuk') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#115a39] transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg> Lihat Pengajuan Masuk</a>
    </div>

    {{-- Stat cards: template grid sama dengan POPT --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        @php $cards = [
            ['label'=>'Pengajuan Masuk','value'=>'0','meta'=>'total','icon'=>'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4','color'=>'#e8f4ed','href'=>route('knowledge.op.pengajuan-masuk')],
            ['label'=>'Menunggu Validasi','value'=>'0','meta'=>'perlu ditinjau','icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z','color'=>'#fff4df','href'=>route('knowledge.op.validasi')],
            ['label'=>'Diterima ke POPT','value'=>'0','meta'=>'diteruskan','icon'=>'M14 10l-2 1m0 0l-2-1m2 1v4m-6 3h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z','color'=>'#eef5e7','href'=>route('knowledge.op.status-kasus')],
            ['label'=>'Ditolak','value'=>'0','meta'=>'dengan alasan','icon'=>'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z','color'=>'#fdeaea','href'=>route('knowledge.op.riwayat-pengajuan')],
        ]; @endphp
        @foreach($cards as $card)
        <a href="{{ $card['href'] }}" class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 hover:-translate-y-0.5 transition">
            <div class="flex items-start justify-between"><span class="flex items-center justify-center w-10 h-10 rounded-xl" style="background:{{ $card['color'] }}"><svg class="w-5 h-5 text-[#176b45]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="{{ $card['icon'] }}"/></svg></span><span class="text-[#a9b4ad]">•••</span></div>
            <div class="mt-5 text-sm font-medium text-[#748179]">{{ $card['label'] }}</div><div class="mt-1 flex items-end gap-2"><span class="text-2xl font-bold tracking-tight text-[#173b29]">{{ $card['value'] }}</span><span class="mb-1 text-[11px] font-semibold text-[#29905d]">{{ $card['meta'] }}</span></div>
        </a>
        @endforeach
    </div>

    {{-- Dua panel: alur pengajuan + ringkasan knowledge (read-only) --}}
    <div class="grid grid-cols-1 xl:grid-cols-[1.08fr_.92fr] gap-5">
        <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-7">
            <div class="flex items-center justify-between mb-5"><div><h2 class="text-base font-bold text-[#173b29]">Alur Pengajuan Kasus</h2><p class="text-xs text-[#89968e] mt-1">OP memvalidasi pengajuan dari Poktan/Gapoktan</p></div></div>
            <div class="space-y-0">
                @php($steps = [
                    ['n' => 1, 'label' => 'Poktan/Gapoktan', 'desc' => 'Mengajukan kasus penanganan', 'color' => '#3d6b78', 'bg' => '#eef5f7'],
                    ['n' => 2, 'label' => 'OP — Validasi', 'desc' => 'Memeriksa & memutuskan: diterima / ditolak (dengan alasan)', 'color' => '#176b45', 'bg' => '#e8f4ed'],
                    ['n' => 3, 'label' => 'POPT — Pemeriksaan Lapangan', 'desc' => 'Memeriksa kondisi tanaman di lokasi', 'color' => '#b8860b', 'bg' => '#fff4df'],
                    ['n' => 4, 'label' => 'Diagnosis & Tindak Lanjut', 'desc' => 'Hasil diagnosis CF + tindak lanjut penanganan', 'color' => '#7c3aed', 'bg' => '#f2edf8'],
                ])
                @foreach($steps as $i => $step)
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <span class="flex items-center justify-center w-9 h-9 rounded-full font-bold text-sm flex-shrink-0" style="background:{{ $step['bg'] }};color:{{ $step['color'] }}">{{ $step['n'] }}</span>
                        @if($i < count($steps) - 1)<span class="w-px flex-1 bg-[#e4ece7] my-1"></span>@endif
                    </div>
                    <div class="pb-6 min-w-0">
                        <div class="text-sm font-semibold text-[#173b29]">{{ $step['label'] }}</div>
                        <div class="text-xs text-[#8b9790] mt-0.5">{{ $step['desc'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-7">
            <div class="flex items-center justify-between mb-5"><div><h2 class="text-base font-bold text-[#173b29]">Ringkasan Knowledge Aktif</h2><p class="text-xs text-[#89968e] mt-1">Referensi read-only sebagai konteks validasi</p></div><a href="{{ route('knowledge.penyakit.index') }}" class="text-xs font-semibold text-[#176b45] hover:underline">Lihat penyakit →</a></div>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl border border-[#eef3ef] p-4"><div class="text-2xl font-bold text-[#176b45]">{{ $stats['penyakit_aktif'] }}</div><div class="text-xs text-[#748179] mt-1">Penyakit aktif</div></div>
                <div class="rounded-xl border border-[#eef3ef] p-4"><div class="text-2xl font-bold text-[#176b45]">{{ $stats['gejala_aktif'] }}</div><div class="text-xs text-[#748179] mt-1">Gejala aktif</div></div>
                <div class="rounded-xl border border-[#eef3ef] p-4"><div class="text-2xl font-bold text-[#176b45]">{{ $stats['solusi_aktif'] }}</div><div class="text-xs text-[#748179] mt-1">Solusi aktif</div></div>
                <div class="rounded-xl border border-[#eef3ef] p-4"><div class="text-2xl font-bold text-[#176b45]">{{ $stats['aturan_cf_aktif'] }}</div><div class="text-xs text-[#748179] mt-1">Aturan CF aktif</div></div>
            </div>
            <div class="mt-5 rounded-lg bg-[#eef6f1] border border-[#d6ebe0] p-3.5 flex items-start gap-2.5">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-[#176b45]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-xs text-[#2d6b4a]">Sebagai OP Anda memiliki akses <strong>read-only</strong> ke data knowledge. Perubahan knowledge dikelola oleh POPT.</p>
            </div>
        </section>
    </div>

    {{-- Info integrasi M2 --}}
    <div class="rounded-xl bg-white border border-[#e6eee8] px-5 py-4 flex items-center gap-3">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#fff4df] text-[#b8860b] flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </span>
        <p class="text-xs text-[#77847c]">Data pengajuan kasus akan tersedia setelah modul <strong>Diagnosis &amp; Kasus (Mahasiswa 2)</strong> terintegrasi. Struktur menu dan dashboard OP sudah siap dipakai.</p>
    </div>
</div>
@endsection
