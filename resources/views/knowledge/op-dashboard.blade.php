@extends('layouts.app')

@section('title', 'Dashboard Operator')
@section('subtitle', 'Review permohonan dan monitoring kasus')

@section('content')
<div class="mx-auto max-w-[1500px] space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-wide text-[#176b45]">Operator UPTD</p><h1 class="mt-1 text-2xl font-bold text-[#173b29]">Selamat datang, {{ auth()->user()?->name }}</h1><p class="mt-1 text-sm text-[#77847c]">Kelola knowledge dan proses alur permohonan penanganan.</p></div>
        <a href="{{ route('operator.permohonan') }}" class="rounded-lg bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white">Lihat Permohonan Masuk</a>
    </div>
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach([
            ['label' => 'Pengajuan Masuk', 'value' => $opStats['masuk'], 'href' => route('operator.permohonan')],
            ['label' => 'Sedang Direview', 'value' => $opStats['review'], 'href' => route('operator.permohonan', ['status' => 'sedang_direview'])],
            ['label' => 'Kasus Diterima', 'value' => $opStats['diterima'], 'href' => route('operator.kasus.index')],
            ['label' => 'Permohonan Ditolak', 'value' => $opStats['ditolak'], 'href' => route('operator.permohonan', ['status' => 'ditolak'])],
        ] as $card)
            <a href="{{ $card['href'] }}" class="rounded-xl border border-[#e6eee8] bg-white p-5 hover:border-[#176b45]"><div class="text-2xl font-bold text-[#176b45]">{{ $card['value'] }}</div><div class="mt-1 text-sm text-gray-600">{{ $card['label'] }}</div></a>
        @endforeach
    </div>
    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-xl border border-[#e6eee8] bg-white p-6"><h2 class="font-bold text-[#173b29]">Alur kerja aktif</h2><ol class="mt-4 space-y-3 text-sm text-gray-700"><li>1. Poktan mengajukan berdasarkan diagnosis.</li><li>2. Operator mereview lalu menerima atau menolak dengan alasan.</li><li>3. Kasus diterima menunggu penugasan POPT.</li><li>4. POPT memperbarui status progres sampai selesai.</li></ol></section>
        <section class="rounded-xl border border-[#e6eee8] bg-white p-6"><h2 class="font-bold text-[#173b29]">Knowledge aktif</h2><p class="mt-2 text-sm text-gray-600">Operator memiliki hak CRUD dan publikasi knowledge sesuai RBAC final.</p><div class="mt-4 grid grid-cols-2 gap-3 text-sm"><a class="rounded-lg bg-[#f7faf8] p-3 text-[#176b45]" href="{{ route('knowledge.penyakit.index') }}">{{ $stats['penyakit_aktif'] }} Penyakit aktif</a><a class="rounded-lg bg-[#f7faf8] p-3 text-[#176b45]" href="{{ route('knowledge.gejala.index') }}">{{ $stats['gejala_aktif'] }} Gejala aktif</a><a class="rounded-lg bg-[#f7faf8] p-3 text-[#176b45]" href="{{ route('knowledge.solusi.index') }}">{{ $stats['solusi_aktif'] }} Solusi aktif</a><a class="rounded-lg bg-[#f7faf8] p-3 text-[#176b45]" href="{{ route('knowledge.aturan-cf.index') }}">{{ $stats['aturan_cf_aktif'] }} Aturan CF aktif</a></div></section>
    </div>
</div>
@endsection
