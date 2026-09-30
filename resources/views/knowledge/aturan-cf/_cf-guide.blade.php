@php
    $guideMethod = $cfMethod ?? null;
    $guideScale = collect($guideMethod?->scaleOptions() ?? [])
        ->filter(fn (array $option): bool => (float) $option['cf'] > 0)
        ->values();
    $guideReference = $guideMethod?->referenceLabel();
    if (! $guideReference || $guideReference === 'Belum tersedia') {
        $guideReference = 'Referensi metodologi belum tersedia.';
    }
@endphp

<details open class="rounded-xl border border-[#cfe5d6] bg-[#f7fcf9] shadow-sm">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 sm:px-6">
        <div>
            <h2 class="text-base font-bold text-[#173b29]">Pedoman Penentuan Nilai CF</h2>
            <p class="mt-1 text-sm text-[#66746c]">Ringkasan skala hubungan gejala dan penyakit yang digunakan SIPAKARBUN.</p>
        </div>
        <span class="shrink-0 rounded-full bg-[#e8f4ed] px-3 py-1 text-xs font-semibold text-[#176b45]">Skala baku 0–1</span>
    </summary>
    <div class="border-t border-[#dbece1] px-5 pb-5 pt-4 sm:px-6">
        <div class="grid gap-4 lg:grid-cols-[1.25fr_.75fr]">
            <div>
                <p class="text-sm leading-6 text-[#315e47]">SIPAKARBUN menggunakan <strong>Expert Elicitation</strong> untuk menilai seberapa kuat suatu gejala mendukung suatu penyakit. Nilai CF di bawah ini menunjukkan <strong>Kekuatan hubungan gejala → penyakit</strong>.</p>
                <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-5">
                    @forelse($guideScale as $option)
                        <div class="rounded-lg border border-[#dbe5df] bg-white px-3 py-3 text-center">
                            <p class="text-xs font-semibold leading-4 text-[#526159]">{{ $option['term'] }}</p>
                            <p class="mt-1 font-mono text-lg font-bold text-[#176b45]">{{ number_format((float) $option['cf'], 2, ',', '.') }}</p>
                        </div>
                    @empty
                        <p class="col-span-full rounded-lg border border-dashed border-[#dbe5df] bg-white px-3 py-3 text-sm text-[#66746c]">Skala CF belum tersedia.</p>
                    @endforelse
                </div>
                <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-xs leading-5 text-amber-900">Nilai CF bukan tingkat keparahan gejala, jumlah bercak, persentase kerusakan tanaman, atau keyakinan pengguna saat diagnosis. Jika gejala tidak mendukung atau netral, jangan buat hubungan penyakit untuk gejala tersebut.</p>
            </div>
            <dl class="rounded-lg border border-[#dbe5df] bg-white p-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-[#66746c]">Metode</dt>
                    <dd class="mt-1 font-semibold text-[#173b29]">{{ $guideMethod?->name ?? 'Belum tersedia' }}</dd>
                </div>
                <div class="mt-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-[#66746c]">Versi</dt>
                    <dd class="mt-1 text-[#315e47]">{{ $guideMethod?->version ?? 'Belum tersedia' }}</dd>
                </div>
                <div class="mt-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-[#66746c]">Referensi Metodologi CF</dt>
                    <dd class="mt-1 break-words text-[#315e47]">
                        @if($guideMethod?->reference_url)
                            <a href="{{ $guideMethod->reference_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-[#176b45] underline decoration-[#9dc9ab] underline-offset-2 hover:text-[#0f4b31]">{{ $guideReference }}</a>
                            @if($guideMethod->reference_doi)<span class="mt-1 block text-xs text-[#66746c]">DOI: {{ $guideMethod->reference_doi }}</span>@endif
                        @else
                            {{ $guideReference }}
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
        <p class="mt-4 text-xs leading-5 text-[#66746c]">CF Pakar adalah kekuatan hubungan yang ditetapkan dari pedoman ini. CF User dihasilkan terpisah dari pilihan pengguna pada saat diagnosis.</p>
    </div>
</details>
