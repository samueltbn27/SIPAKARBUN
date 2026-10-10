@extends('layouts.app')

@section('title', 'Laporan Gejala Saya')

@section('content')
    <x-page-header
        title="Laporan Gejala Saya"
        subtitle="Pantau laporan gejala yang Anda kirim kepada POPT."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Diagnosis', 'url' => route('diagnosis.index')], ['label' => 'Laporan Gejala Saya']]"
    />

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="status">{{ session('success') }}</div>
    @endif

    <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-[#dbe5df] bg-[#f5faf6] p-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-[#66746c]">Gejala baru akan ditinjau POPT. Laporan belum digunakan untuk diagnosis.</p>
        <a href="{{ route('diagnosis.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-[#176b45] px-4 py-2 text-sm font-semibold text-white hover:bg-[#173b29]">Kembali ke Diagnosis</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-[#e4ece7] bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[720px] w-full divide-y divide-[#eef3ef] text-left text-sm">
                <thead class="bg-[#fafcfb] text-xs font-bold uppercase tracking-wide text-[#8a9990]">
                    <tr>
                        <th class="px-4 py-3">Nomor laporan</th>
                        <th class="px-4 py-3">Komoditas</th>
                        <th class="px-4 py-3">Gejala yang diamati</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Dikirim</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#eef3ef]">
                    @forelse ($laporan as $item)
                        <tr class="align-top hover:bg-[#fafcfb]">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-[#176b45]">{{ $item->report_code }}</td>
                            <td class="px-4 py-3 font-medium text-[#173b29]">{{ $item->commodity_name_snapshot }}</td>
                            <td class="max-w-sm px-4 py-3 text-[#66746c]">{{ \Illuminate\Support\Str::limit($item->description, 100) }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full bg-[#eef3ef] px-2.5 py-1 text-xs font-semibold text-[#53645a]">{{ $item->statusLabel() }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-[#66746c]">{{ $item->created_at?->format('d M Y, H:i') }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('diagnosis.reports.show', $item) }}" class="font-semibold text-[#176b45] hover:underline">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-[#8a9990]">Anda belum mengirim laporan gejala.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-[#eef3ef] px-4 py-3">{{ $laporan->links() }}</div>
    </div>
@endsection
