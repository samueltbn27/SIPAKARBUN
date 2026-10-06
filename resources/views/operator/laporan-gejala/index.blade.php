@extends('layouts.app')

@section('title', 'Review Gejala Baru')

@section('content')
    <x-page-header
        title="Review Gejala Baru"
        subtitle="Tinjau kajian POPT atas laporan gejala Poktan sebelum relasi penyakit & CF dibuat."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('knowledge.dashboard')], ['label' => 'Review Gejala Baru']]"
    />

    @if ($menungguCount > 0)
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="status">
            <span class="font-semibold">{{ $menungguCount }}</span> kajian POPT menunggu review Anda. Tanpa persetujuan, draft gejala tidak dapat direlasikan maupun dipublish.
        </div>
    @endif

    <form method="GET" action="{{ route('operator.laporan-gejala.index') }}" class="mb-5 flex flex-col gap-3 rounded-2xl border border-[#e4ece7] bg-white p-4 sm:flex-row sm:items-end">
        <label for="filter" class="w-full text-xs font-semibold text-[#66746c] sm:max-w-xs">Filter review
            <select id="filter" name="filter" class="mt-1 w-full rounded-xl border-[#dbe5df] text-sm focus:border-[#176b45] focus:ring-[#176b45]/20">
                @foreach (['menunggu' => 'Menunggu review', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'semua' => 'Semua'] as $value => $label)
                    <option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="min-h-11 rounded-xl bg-[#176b45] px-4 py-2 text-sm font-semibold text-white hover:bg-[#173b29]">Terapkan filter</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-[#e4ece7] bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[820px] w-full divide-y divide-[#eef3ef] text-left text-sm">
                <thead class="bg-[#fafcfb] text-xs font-bold uppercase tracking-wide text-[#8a9990]"><tr><th class="px-4 py-3">Laporan</th><th class="px-4 py-3">Poktan / POPT</th><th class="px-4 py-3">Draft Gejala</th><th class="px-4 py-3">Review</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-[#eef3ef]">
                    @forelse ($laporan as $item)
                        <tr class="align-top hover:bg-[#fafcfb]">
                            <td class="whitespace-nowrap px-4 py-3"><span class="block font-mono text-xs font-semibold text-[#176b45]">{{ $item->report_code }}</span><span class="mt-1 block text-xs text-[#8a9990]">{{ $item->commodity_name_snapshot }} · {{ $item->reviewed_at?->format('d M Y, H:i') ?? '—' }}</span></td>
                            <td class="px-4 py-3 text-[#34483b]">{{ $item->reporter?->name ?? '—' }}<span class="block text-xs text-[#8a9990]">Kajian: {{ $item->reviewer?->name ?? '—' }}</span></td>
                            <td class="px-4 py-3 font-medium text-[#173b29]">{{ $item->gejala?->nama ?? '—' }}<span class="block text-xs text-[#8a9990]">Status: {{ $item->gejala?->status ?? '—' }}</span></td>
                            <td class="px-4 py-3">
                                @if ($item->operator_review === \App\Models\LaporanGejala::REVIEW_SETUJU)
                                    <span class="inline-flex rounded-full bg-[#e8f4ed] px-2.5 py-1 text-xs font-semibold text-[#176b45]">Disetujui</span>
                                @elseif ($item->operator_review === \App\Models\LaporanGejala::REVIEW_DITOLAK)
                                    <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Ditolak</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Menunggu review</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('operator.laporan-gejala.show', $item) }}" class="font-semibold text-[#176b45] hover:underline">Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-[#8a9990]">Tidak ada kajian POPT pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-[#eef3ef] px-4 py-3">{{ $laporan->links() }}</div>
    </div>
@endsection
