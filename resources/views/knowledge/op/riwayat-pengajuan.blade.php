@extends('layouts.app')

@section('title', 'Riwayat Pengajuan')
@section('subtitle', 'Histori keputusan pengajuan kasus')

@section('content')
<div class="max-w-[1500px] mx-auto space-y-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-[#8c9890] mb-2"><span>Pengajuan Kasus</span><span>/</span><span class="text-[#176b45]">Riwayat Pengajuan</span></div>
        <h1 class="text-2xl font-bold tracking-tight text-[#173b29]">Riwayat Pengajuan</h1>
        <p class="mt-1 text-sm text-[#77847c]">Histori seluruh keputusan validasi: diterima maupun ditolak (beserta alasan).</p>
    </div>

    <div class="soft-card rounded-xl border border-[#e6eee8] bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#eef3ef]">
                <thead>
                    <tr class="bg-[#f7faf8]">
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Kode</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Poktan/Gapoktan</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Keputusan</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Alasan / Catatan</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0f4f1] bg-white">
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#f3f8f4] text-[#b0c4ba] mb-4">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <p class="text-sm font-medium text-[#8b9790]">Belum ada riwayat keputusan.</p>
                            <p class="text-xs text-[#b0bab3] mt-1">Riwayat akan terisi setelah modul Diagnosis &amp; Kasus (Mahasiswa 2) terintegrasi.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
