@extends('layouts.app')

@section('title', 'Laporan Gejala Masuk')

@section('content')
    <x-page-header
        title="Laporan Gejala Masuk"
        subtitle="Tinjau pengamatan Poktan sebelum menjadi draft basis pengetahuan."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('knowledge.dashboard')], ['label' => 'Laporan Gejala']]"
    />

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="status">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('popt.laporan-gejala.index') }}" class="mb-5 flex flex-col gap-3 rounded-2xl border border-[#e4ece7] bg-white p-4 sm:flex-row sm:items-end">
        <label for="status" class="w-full text-xs font-semibold text-[#66746c] sm:max-w-xs">Filter status
            <select id="status" name="status" class="mt-1 w-full rounded-xl border-[#dbe5df] text-sm focus:border-[#176b45] focus:ring-[#176b45]/20">
                <option value="">Semua status</option>
                @foreach ([\App\Models\LaporanGejala::STATUS_DIAJUKAN => 'Menunggu tinjauan', \App\Models\LaporanGejala::STATUS_PERLU_INFORMASI => 'Perlu informasi tambahan', \App\Models\LaporanGejala::STATUS_DUPLIKAT => 'Duplikat', \App\Models\LaporanGejala::STATUS_DRAFT_DIBUAT => 'Draft dibuat'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="min-h-11 rounded-xl bg-[#176b45] px-4 py-2 text-sm font-semibold text-white hover:bg-[#173b29]">Terapkan filter</button>
        @if ($status)<a href="{{ route('popt.laporan-gejala.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-[#dbe5df] px-4 py-2 text-sm font-semibold text-[#66746c]">Reset</a>@endif
    </form>

    <div class="overflow-hidden rounded-2xl border border-[#e4ece7] bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full divide-y divide-[#eef3ef] text-left text-sm">
                <thead class="bg-[#fafcfb] text-xs font-bold uppercase tracking-wide text-[#8a9990]"><tr><th class="px-4 py-3">Laporan</th><th class="px-4 py-3">Poktan</th><th class="px-4 py-3">Komoditas</th><th class="px-4 py-3">Gejala</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-[#eef3ef]">
                    @forelse ($laporan as $item)
                        <tr class="align-top hover:bg-[#fafcfb]">
                            <td class="whitespace-nowrap px-4 py-3"><span class="block font-mono text-xs font-semibold text-[#176b45]">{{ $item->report_code }}</span><span class="mt-1 block text-xs text-[#8a9990]">{{ $item->created_at?->format('d M Y, H:i') }}</span></td>
                            <td class="px-4 py-3 font-medium text-[#173b29]">{{ $item->reporter?->name ?? 'Akun tidak tersedia' }}</td>
                            <td class="px-4 py-3 text-[#34483b]">{{ $item->commodity_name_snapshot }}</td>
                            <td class="max-w-sm px-4 py-3 text-[#66746c]">{{ \Illuminate\Support\Str::limit($item->description, 90) }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full bg-[#eef3ef] px-2.5 py-1 text-xs font-semibold text-[#53645a]">{{ $item->statusLabel() }}</span></td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('popt.laporan-gejala.show', $item) }}" class="font-semibold text-[#176b45] hover:underline">Tinjau</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-[#8a9990]">Belum ada laporan gejala untuk ditinjau.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-[#eef3ef] px-4 py-3">{{ $laporan->links() }}</div>
    </div>
@endsection
