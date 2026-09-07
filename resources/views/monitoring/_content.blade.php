<section id="dashboard-monitoring" data-monitoring-content class="space-y-6" aria-labelledby="monitoring-kpi-heading">
    <p data-dashboard-loading class="loading-surface flex items-center justify-center gap-3 rounded-xl px-5 py-4 text-center text-sm font-medium text-[#526159]" role="status" aria-live="polite">
        <span class="loading-dots" aria-hidden="true"><span></span><span></span><span></span></span>
        Menyiapkan ringkasan monitoring...
    </p>
    <p data-dashboard-error hidden class="rounded-xl border border-[#e4c5c0] bg-[#fff8f7] px-5 py-4 text-center text-sm font-medium text-[#8d3d35]" role="alert">
        Data monitoring tidak dapat dimuat.
    </p>

    <section aria-labelledby="monitoring-kpi-heading">
        <div class="mb-3 flex items-end justify-between gap-3">
            <div>
                <h2 id="monitoring-kpi-heading" class="text-base font-bold text-[#173b29]">Ringkasan Kasus</h2>
                <p class="mt-1 text-xs text-[#89968e]">Angka dan grafik mengikuti filter monitoring di atas.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Total Kasus</p>
                <p data-dashboard-kpi="total" class="mt-3 text-3xl font-bold text-[#173b29]">0</p>
                <p class="mt-1 text-xs text-[#77847c]">Kasus yang sesuai filter</p>
            </article>
            <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Kasus Aktif</p>
                <p data-dashboard-kpi="active" class="mt-3 text-3xl font-bold text-[#176b45]">0</p>
                <p class="mt-1 text-xs text-[#77847c]">Seluruh status selain selesai</p>
            </article>
            <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Selesai</p>
                <p data-dashboard-kpi="completed" class="mt-3 text-3xl font-bold text-[#526159]">0</p>
                <p class="mt-1 text-xs text-[#77847c]">Status selesai</p>
            </article>
            <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Ditunda</p>
                <p data-dashboard-kpi="postponed" class="mt-3 text-3xl font-bold text-[#80610a]">0</p>
                <p class="mt-1 text-xs text-[#77847c]">Status ditunda</p>
            </article>
        </div>
    </section>

    <p data-dashboard-dataset-empty hidden class="rounded-xl border border-dashed border-[#d6e0d9] bg-[#f7faf8] px-5 py-4 text-center text-sm font-medium text-[#526159]" role="status">
        Belum ada data kasus penanganan.
    </p>
    <p data-dashboard-empty hidden class="rounded-xl border border-dashed border-[#d6e0d9] bg-[#f7faf8] px-5 py-4 text-center text-sm font-medium text-[#526159]" role="status">
        Tidak ada kasus yang sesuai dengan filter yang dipilih.
    </p>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-2" aria-label="Visualisasi monitoring kasus">
        <article class="monitoring-chart-card soft-card rounded-2xl border border-[#e6eee8] border-l-4 border-l-[#176b45] bg-white p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-[#8a9990]">Gambaran utama</p>
                    <h2 class="text-base font-bold text-[#173b29]">Kasus per Status</h2>
                </div>
                <span class="rounded-full bg-[#eef6f1] px-2.5 py-1 text-[10px] font-bold tracking-[0.12em] text-[#2d6b4a]">01</span>
            </div>
            <p data-dashboard-chart-summary="status" class="mt-2 min-h-[3rem] text-xs leading-5 text-[#89968e]">Menyiapkan ringkasan status...</p>
            <div class="monitoring-chart-stage relative mt-4 h-[300px] rounded-xl border border-[#edf2ee] px-3 py-4 sm:h-[320px]">
                <canvas data-dashboard-chart="status" role="img" aria-label="Diagram jumlah kasus berdasarkan status"></canvas>
            </div>
        </article>

        <article class="monitoring-chart-card soft-card rounded-2xl border border-[#e6eee8] border-l-4 border-l-[#3d8eb9] bg-white p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-[#8a9990]">Komposisi data</p>
                    <h2 class="text-base font-bold text-[#173b29]">Kasus per Komoditas</h2>
                </div>
                <span class="rounded-full bg-[#edf5fa] px-2.5 py-1 text-[10px] font-bold tracking-[0.12em] text-[#246384]">02</span>
            </div>
            <p data-dashboard-chart-summary="commodity" class="mt-2 min-h-[3rem] text-xs leading-5 text-[#89968e]">Menyiapkan ringkasan komoditas...</p>
            <div class="monitoring-chart-stage relative mt-4 h-[300px] rounded-xl border border-[#edf2ee] px-3 py-4 sm:h-[320px]">
                <canvas data-dashboard-chart="commodity" role="img" aria-label="Diagram jumlah kasus berdasarkan komoditas"></canvas>
            </div>
        </article>

        <article class="monitoring-chart-card soft-card rounded-2xl border border-[#e6eee8] border-l-4 border-l-[#b8860b] bg-white p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-[#8a9990]">Sebaran wilayah</p>
                    <h2 class="text-base font-bold text-[#173b29]">Kasus per Kabupaten/Kota</h2>
                </div>
                <span class="rounded-full bg-[#fbf4df] px-2.5 py-1 text-[10px] font-bold tracking-[0.12em] text-[#80610a]">03</span>
            </div>
            <p data-dashboard-chart-summary="regency" class="mt-2 min-h-[3rem] text-xs leading-5 text-[#89968e]">Menyiapkan ringkasan wilayah...</p>
            <div class="monitoring-chart-stage relative mt-4 h-[300px] rounded-xl border border-[#edf2ee] px-3 py-4 sm:h-[320px]">
                <canvas data-dashboard-chart="regency" role="img" aria-label="Diagram jumlah kasus berdasarkan kabupaten atau kota"></canvas>
            </div>
        </article>

        <article class="monitoring-chart-card soft-card rounded-2xl border border-[#e6eee8] border-l-4 border-l-[#8b6cc7] bg-white p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-[#8a9990]">Prioritas penanganan</p>
                    <h2 class="text-base font-bold text-[#173b29]">Kasus per Penyakit</h2>
                </div>
                <span class="rounded-full bg-[#f0eafa] px-2.5 py-1 text-[10px] font-bold tracking-[0.12em] text-[#5e4a9a]">04</span>
            </div>
            <p data-dashboard-chart-summary="disease" class="mt-2 min-h-[3rem] text-xs leading-5 text-[#89968e]">Menyiapkan ringkasan penyakit...</p>
            <div class="monitoring-chart-stage relative mt-4 h-[300px] rounded-xl border border-[#edf2ee] px-3 py-4 sm:h-[320px]">
                <canvas data-dashboard-chart="disease" role="img" aria-label="Diagram jumlah kasus berdasarkan penyakit"></canvas>
            </div>
        </article>
    </section>
</section>
