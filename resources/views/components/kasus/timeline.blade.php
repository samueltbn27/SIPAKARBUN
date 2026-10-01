@props(['riwayat', 'judul' => 'Riwayat progres'])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-[#e6eee8] bg-white p-5']) }}>
    <h2 class="font-bold text-[#173b29]">{{ $judul }}</h2>
    <ol class="mt-4 space-y-3">
        @forelse($riwayat as $item)
            <li class="border-l-2 border-[#cfe2d5] pl-3 text-sm">
                <strong>{{ config('kasus.labels.'.$item->status, $item->status) }}</strong><span class="text-gray-500"> · {{ $item->created_at?->format('d-m-Y H:i') }}</span>
                <p class="text-gray-600">{{ $item->catatan ?: 'Tanpa catatan' }}</p>
            </li>
        @empty
            <li class="text-sm text-gray-500">Belum ada riwayat.</li>
        @endforelse
    </ol>
</section>
