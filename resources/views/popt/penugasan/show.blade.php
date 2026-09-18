@extends('layouts.app')

@section('title', 'Detail Penugasan')

@php
    $statusLabels = [
        'diterima' => 'Diterima',
        'ditugaskan' => 'Ditugaskan',
        'sedang_direview' => 'Sedang Direview',
        'ditunda' => 'Ditunda',
        'siap_dieksekusi' => 'Siap Dieksekusi',
        'dalam_pelaksanaan' => 'Dalam Pelaksanaan',
        'selesai' => 'Selesai',
    ];
    $extensionStatusLabels = [
        'pending' => 'Menunggu Persetujuan Operator',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];
    $isActive = $ownsActiveAssignment && $kasus->current_status !== 'selesai';
    $isAccepted = $activeAssignment?->accepted_at !== null;
    $pendingExtension = $activeAssignment?->extensionRequests?->firstWhere('status', 'pending');
@endphp

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <a href="{{ route('popt.penugasan') }}" class="text-sm text-[#176b45]">← Penugasan saya</a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold text-[#173b29]">{{ $kasus->kasus_code }}</h1>
            <span class="rounded-full bg-[#eef6f1] px-3 py-1.5 text-sm font-semibold text-[#176b45]">{{ $statusLabels[$kasus->current_status] ?? $kasus->current_status }}</span>
            <span class="rounded-full bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-700">{{ $monitoring['label'] }}</span>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <p class="font-semibold">Periksa kembali data yang dikirim.</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="grid gap-5 rounded-xl border border-[#e6eee8] bg-white p-5 lg:grid-cols-2">
        <div>
            <h2 class="font-bold text-[#173b29]">Detail Kasus</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-gray-500">Poktan</dt><dd>{{ $kasus->permohonan?->kelompok_tani_name_snapshot ?: '-' }}</dd></div>
                <div><dt class="text-gray-500">Pemohon</dt><dd>{{ $kasus->permohonan?->creator?->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Komoditas</dt><dd>{{ $kasus->komoditas_name_snapshot ?: '-' }}</dd></div>
                <div><dt class="text-gray-500">Penyakit / hasil diagnosis</dt><dd>{{ $kasus->penyakit_name_snapshot ?: '-' }}</dd></div>
                <div><dt class="text-gray-500">Lokasi kasus</dt><dd>{{ $kasus->latitude_kasus ?? '-' }}, {{ $kasus->longitude_kasus ?? '-' }}</dd></div>
            </dl>
        </div>
        <div>
            <h2 class="font-bold text-[#173b29]">Informasi Penugasan</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-gray-500">Ditugaskan kepada</dt><dd>{{ $assignment?->popt?->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Tanggal Penugasan</dt><dd>{{ $assignment?->assigned_at?->format('d-m-Y H:i') ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Target Penyelesaian</dt><dd>{{ $assignment?->deadline_at?->format('d-m-Y H:i') ?? 'Belum ditentukan' }}</dd></div>
                <div><dt class="text-gray-500">Catatan Operator</dt><dd class="whitespace-pre-line">{{ $assignment?->catatan ?: 'Tidak ada catatan.' }}</dd></div>
                <div><dt class="text-gray-500">Status Penerimaan</dt><dd>{{ $assignment?->accepted_at ? 'Penugasan diterima pada '.$assignment->accepted_at->format('d-m-Y H:i') : 'Menunggu POPT menerima penugasan' }}</dd></div>
                <div><dt class="text-gray-500">Status Waktu</dt><dd>{{ $monitoring['is_overdue'] ? 'Melewati Batas Waktu' : ($assignment?->deadline_at ? ($monitoring['label'] === 'Selesai' ? 'Selesai' : 'Tepat Waktu') : 'Target belum tersedia') }}</dd></div>
            </dl>
        </div>
    </section>

    @if($isActive && ! $isAccepted)
        <section class="rounded-xl border border-[#cfe2d5] bg-[#f5faf6] p-5">
            <h2 class="font-bold text-[#173b29]">Penugasan Baru</h2>
            <p class="mt-1 text-sm text-[#66746c]">Terima penugasan untuk mulai mencatat kegiatan penanganan.</p>
            <form method="POST" action="{{ route('popt.penugasan.accept', $kasus->id) }}" class="mt-4">
                @csrf
                <button class="min-h-11 w-full rounded-lg bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white sm:w-auto">Terima Penugasan</button>
            </form>
        </section>
    @elseif($isActive && $isAccepted)
        <section class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-xl border border-[#e6eee8] bg-white p-5">
                <h2 class="font-bold text-[#173b29]">Tambah Progress</h2>
                <p class="mt-1 text-sm text-gray-500">Catat kegiatan lapangan sebagai histori yang tidak diubah.</p>
                <form method="POST" action="{{ route('popt.penugasan.progress', $kasus->id) }}" class="mt-4 space-y-3">
                    @csrf
                    <label for="catatan-progress" class="block text-sm font-medium text-gray-700">Catatan Progress <span class="text-red-600">*</span></label>
                    <textarea id="catatan-progress" name="catatan" rows="5" maxlength="2000" required class="w-full rounded-lg border-gray-300 text-sm" placeholder="Contoh: Pemeriksaan kondisi tanaman di lapangan.">{{ old('catatan') }}</textarea>
                    <button class="min-h-11 w-full rounded-lg bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white">Tambah Progress</button>
                </form>
            </div>

            <div class="rounded-xl border border-[#e6eee8] bg-white p-5">
                <h2 class="font-bold text-[#173b29]">Ajukan Perpanjangan</h2>
                @if($pendingExtension)
                    <p class="mt-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Permintaan perpanjangan sedang menunggu keputusan Operator.</p>
                @elseif(! $activeAssignment->deadline_at)
                    <p class="mt-2 text-sm text-gray-500">Target Penyelesaian belum ditentukan, sehingga perpanjangan belum dapat diajukan.</p>
                @else
                    <form method="POST" action="{{ route('popt.penugasan.extension', $kasus->id) }}" class="mt-4 space-y-3">
                        @csrf
                        <div class="rounded-lg bg-gray-50 p-3 text-sm"><span class="block text-xs text-gray-500">Target Penyelesaian Saat Ini</span>{{ $activeAssignment->deadline_at->format('d-m-Y H:i') }}</div>
                        <label for="proposed-deadline" class="block text-sm font-medium text-gray-700">Usulan Target Baru <span class="text-red-600">*</span></label>
                        <input id="proposed-deadline" name="proposed_deadline_at" type="datetime-local" required class="w-full rounded-lg border-gray-300 text-sm" value="{{ old('proposed_deadline_at') }}">
                        <label for="extension-reason" class="block text-sm font-medium text-gray-700">Alasan Perpanjangan <span class="text-red-600">*</span></label>
                        <textarea id="extension-reason" name="reason" rows="3" maxlength="2000" required class="w-full rounded-lg border-gray-300 text-sm" placeholder="Jelaskan kendala atau kebutuhan waktu tambahan.">{{ old('reason') }}</textarea>
                        <button class="min-h-11 w-full rounded-lg border border-[#176b45] px-4 py-2.5 text-sm font-semibold text-[#176b45]">Ajukan Perpanjangan</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="rounded-xl border border-[#e6eee8] bg-white p-5">
            <h2 class="font-bold text-[#173b29]">Selesaikan Penanganan</h2>
            <p class="mt-1 text-sm text-gray-500">Kirim laporan hasil penanganan dan minimal satu foto dokumentasi untuk menyelesaikan kasus.</p>
            <form method="POST" action="{{ route('popt.penugasan.complete', $kasus->id) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="ringkasan-tindakan" class="block text-sm font-medium text-gray-700">Ringkasan Tindakan <span class="text-red-600">*</span></label>
                    <textarea id="ringkasan-tindakan" name="ringkasan_tindakan" rows="4" maxlength="10000" required class="mt-1 w-full rounded-lg border-gray-300 text-sm">{{ old('ringkasan_tindakan') }}</textarea>
                </div>
                <div>
                    <label for="hasil-penanganan" class="block text-sm font-medium text-gray-700">Hasil Penanganan <span class="text-red-600">*</span></label>
                    <textarea id="hasil-penanganan" name="hasil_penanganan" rows="4" maxlength="10000" required class="mt-1 w-full rounded-lg border-gray-300 text-sm">{{ old('hasil_penanganan') }}</textarea>
                </div>
                <div>
                    <label for="rekomendasi" class="block text-sm font-medium text-gray-700">Rekomendasi / Tindak Lanjut <span class="text-red-600">*</span></label>
                    <textarea id="rekomendasi" name="rekomendasi" rows="4" maxlength="10000" required class="mt-1 w-full rounded-lg border-gray-300 text-sm">{{ old('rekomendasi') }}</textarea>
                </div>
                <div>
                    <label for="catatan-tambahan" class="block text-sm font-medium text-gray-700">Catatan Tambahan</label>
                    <textarea id="catatan-tambahan" name="catatan_tambahan" rows="3" maxlength="10000" class="mt-1 w-full rounded-lg border-gray-300 text-sm">{{ old('catatan_tambahan') }}</textarea>
                </div>
                <div>
                    <label for="photos" class="block text-sm font-medium text-gray-700">Dokumentasi Foto <span class="text-red-600">*</span></label>
                    <input id="photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required class="mt-1 block w-full rounded-lg border border-gray-300 p-2 text-sm">
                    <p class="mt-1 text-xs text-gray-500">Minimal 1 dan maksimal 5 foto. Format JPEG, PNG, atau WebP; maksimal 5 MB per foto.</p>
                    <div id="photo-preview" class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4"></div>
                </div>
                <button class="min-h-11 w-full rounded-lg bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white sm:w-auto">Submit Laporan &amp; Selesaikan</button>
            </form>
        </section>
    @elseif($kasus->current_status === 'selesai')
        @if(! $kasus->finalReport)
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">Laporan akhir belum tersedia untuk kasus historis ini.</section>
        @endif
    @endif

    <section class="rounded-xl border border-[#e6eee8] bg-white p-5">
        <h2 class="font-bold text-[#173b29]">Progress</h2>
        <ol class="mt-4 space-y-4">
            @if($assignment?->accepted_at)
                <li class="border-l-2 border-[#176b45] pl-3 text-sm"><strong>Penugasan diterima</strong><span class="block text-gray-500">{{ $assignment->accepted_at->format('d-m-Y H:i') }}</span></li>
            @endif
            @forelse($kasus->progress->sortBy('created_at') as $progress)
                <li class="border-l-2 border-[#cfe2d5] pl-3 text-sm"><strong>{{ $progress->created_at?->format('d-m-Y H:i') }}</strong><p class="mt-1 whitespace-pre-line text-gray-600">{{ $progress->catatan }}</p></li>
            @empty
                @if(! $assignment?->accepted_at)<li class="text-sm text-gray-500">Belum ada update progress.</li>@endif
            @endforelse
        </ol>
    </section>

    <section class="rounded-xl border border-[#e6eee8] bg-white p-5">
        <h2 class="font-bold text-[#173b29]">Riwayat Perpanjangan</h2>
        <div class="mt-4 space-y-4">
            @forelse($assignment?->extensionRequests ?? [] as $extension)
                <article class="rounded-lg border border-[#e6eee8] p-4 text-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-semibold text-[#173b29]">Permintaan Perpanjangan</p><p class="text-xs text-gray-500">Diajukan {{ $extension->created_at?->format('d-m-Y H:i') ?? '-' }}</p></div><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $extensionStatusLabels[$extension->status] ?? 'Riwayat' }}</span></div>
                    <dl class="mt-3 grid gap-2 sm:grid-cols-2"><div><dt class="text-gray-500">Deadline Lama</dt><dd>{{ $extension->current_deadline_at?->format('d-m-Y H:i') ?? 'Belum ditentukan' }}</dd></div><div><dt class="text-gray-500">Usulan Deadline</dt><dd>{{ $extension->proposed_deadline_at?->format('d-m-Y H:i') }}</dd></div><div class="sm:col-span-2"><dt class="text-gray-500">Alasan Perpanjangan</dt><dd class="whitespace-pre-line">{{ $extension->reason }}</dd></div>@if($extension->reviewed_at)<div><dt class="text-gray-500">Keputusan Operator</dt><dd>{{ $extension->reviewer?->name ?? '-' }} · {{ $extension->reviewed_at->format('d-m-Y H:i') }}</dd></div>@endif @if($extension->review_note)<div class="sm:col-span-2"><dt class="text-gray-500">Catatan Keputusan</dt><dd class="whitespace-pre-line">{{ $extension->review_note }}</dd></div>@endif</dl>
                </article>
            @empty
                <p class="text-sm text-gray-500">Belum ada permintaan perpanjangan.</p>
            @endforelse
        </div>
    </section>

    @if($kasus->finalReport)
        <section class="rounded-xl border border-[#cfe2d5] bg-[#f5faf6] p-5">
            <h2 class="font-bold text-[#173b29]">Laporan Hasil Penanganan</h2>
            <p class="mt-1 text-xs text-gray-500">Dikirim {{ $kasus->finalReport->submitted_at?->format('d-m-Y H:i') ?? '-' }} oleh {{ $kasus->finalReport->submitter?->name ?? 'POPT' }}</p>
            <dl class="mt-4 space-y-4 text-sm"><div><dt class="font-semibold text-gray-600">Ringkasan Tindakan</dt><dd class="mt-1 whitespace-pre-line">{{ $kasus->finalReport->ringkasan_tindakan }}</dd></div><div><dt class="font-semibold text-gray-600">Hasil Penanganan</dt><dd class="mt-1 whitespace-pre-line">{{ $kasus->finalReport->hasil_penanganan }}</dd></div><div><dt class="font-semibold text-gray-600">Rekomendasi / Tindak Lanjut</dt><dd class="mt-1 whitespace-pre-line">{{ $kasus->finalReport->rekomendasi }}</dd></div>@if($kasus->finalReport->catatan_tambahan)<div><dt class="font-semibold text-gray-600">Catatan Tambahan</dt><dd class="mt-1 whitespace-pre-line">{{ $kasus->finalReport->catatan_tambahan }}</dd></div>@endif</dl>
            <div class="mt-5"><h3 class="font-semibold text-gray-600">Dokumentasi Foto</h3><div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">@foreach($kasus->finalReport->evidences as $evidence)<a href="{{ Storage::disk('public')->url($evidence->file_path) }}" target="_blank" class="group"><img src="{{ Storage::disk('public')->url($evidence->file_path) }}" alt="{{ $evidence->file_name }}" class="aspect-square w-full rounded-lg border border-[#dbe5df] object-cover"><span class="mt-1 block truncate text-xs text-gray-600 group-hover:text-[#176b45]">{{ $evidence->file_name }}</span></a>@endforeach</div></div>
            <p class="mt-4 text-xs font-medium text-gray-600">Laporan akhir sudah tersimpan dan bersifat read-only.</p>
        </section>
    @endif

    <section class="rounded-xl border border-[#e6eee8] bg-white p-5">
        <h2 class="font-bold text-[#173b29]">Riwayat Status Teknis</h2>
        <ol class="mt-4 space-y-3">
            @forelse($kasus->riwayatStatus as $riwayat)
                <li class="border-l-2 border-[#cfe2d5] pl-3 text-sm"><strong>{{ $statusLabels[$riwayat->status] ?? $riwayat->status }}</strong><span class="text-gray-500"> · {{ $riwayat->created_at?->format('d-m-Y H:i') }}</span><p class="text-gray-600">{{ $riwayat->catatan ?: 'Tanpa catatan' }}</p></li>
            @empty
                <li class="text-sm text-gray-500">Belum ada riwayat status.</li>
            @endforelse
        </ol>
    </section>
</div>

@if($isActive && $isAccepted)
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('photos');
        const preview = document.getElementById('photo-preview');
        if (!input || !preview) return;
        input.addEventListener('change', () => {
            preview.replaceChildren();
            Array.from(input.files || []).forEach((file) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'overflow-hidden rounded-lg border border-[#dbe5df] bg-[#fafcfb]';
                const image = document.createElement('img');
                image.className = 'aspect-square w-full object-cover';
                image.alt = file.name;
                const label = document.createElement('p');
                label.className = 'truncate px-2 py-1 text-xs text-gray-600';
                label.textContent = file.name;
                wrapper.append(image, label);
                preview.append(wrapper);
                const reader = new FileReader();
                reader.addEventListener('load', () => { image.src = reader.result; });
                reader.readAsDataURL(file);
            });
        });
    });
</script>
@endif
@endsection
