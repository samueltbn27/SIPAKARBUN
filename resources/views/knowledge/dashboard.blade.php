@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-[1500px] mx-auto space-y-7">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div><div class="flex items-center gap-2 text-xs text-[#8c9890] mb-2"><span>Knowledge Management</span><span>/</span><span class="text-[#176b45]">Dashboard</span></div><h1 class="text-2xl sm:text-[28px] font-bold tracking-tight text-[#173b29]">Selamat datang, {{ auth()->user()?->name ?? 'Admin KM' }} <span class="text-xl">👋</span></h1><p class="mt-1 text-sm text-[#77847c]">Pantau dan kelola basis pengetahuan perkebunan dalam satu tempat.</p></div>
        <a href="{{ route('knowledge.penyakit.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#115a39] transition"><span class="text-lg leading-none">+</span> Tambah Penyakit</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
        @php $cards = [
            ['label'=>'Komoditas','value'=>$stats['komoditas'],'meta'=>'Aktif','icon'=>'M12 3c-4 2-7 5-7 9a7 7 0 0 0 14 0c0-4-3-7-7-9Z','color'=>'#e8f4ed','href'=>route('knowledge.komoditas.index')],
            ['label'=>'Penyakit','value'=>$stats['penyakit'],'meta'=>$stats['penyakit_aktif'].' Aktif','icon'=>'M12 2 4 5v6c0 5 3.5 9 8 11 4.5-2 8-6 8-11V5l-8-3Z','color'=>'#f2edf8','href'=>route('knowledge.penyakit.index')],
            ['label'=>'Gejala','value'=>$stats['gejala'],'meta'=>$stats['gejala_aktif'].' Aktif','icon'=>'M6 3h12v18H6zM9 7h6M9 11h6M9 15h4','color'=>'#eef5e7','href'=>route('knowledge.gejala.index')],
            ['label'=>'Aturan CF','value'=>$stats['aturan_cf'],'meta'=>$stats['aturan_cf_aktif'].' Aktif','icon'=>'M4 18V6m0 12h16M8 15V9m4 6V5m4 10v-3','color'=>'#fff4df','href'=>route('knowledge.aturan-cf.index')],
            ['label'=>'Solusi','value'=>$stats['solusi'],'meta'=>$stats['solusi_aktif'].' Aktif','icon'=>'M9 18h6m-5 3h4M12 3a6 6 0 0 0-3 11c.6.4 1 1.1 1 2h4c0-.9.4-1.6 1-2a6 6 0 0 0-3-11Z','color'=>'#e7f3f4','href'=>route('knowledge.solusi.index')],
        ]; @endphp
        @foreach($cards as $card)
        <a href="{{ $card['href'] }}" class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 hover:-translate-y-0.5 transition">
            <div class="flex items-start justify-between"><span class="flex items-center justify-center w-10 h-10 rounded-xl" style="background:{{ $card['color'] }}"><svg class="w-5 h-5 text-[#176b45]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="{{ $card['icon'] }}"/></svg></span><span class="text-[#a9b4ad]">•••</span></div>
            <div class="mt-5 text-sm font-medium text-[#748179]">{{ $card['label'] }}</div><div class="mt-1 flex items-end gap-2"><span class="text-2xl font-bold tracking-tight text-[#173b29]">{{ $card['value'] }}</span><span class="mb-1 text-[11px] font-semibold text-[#29905d]">{{ $card['meta'] }}</span></div>
        </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[.92fr_1.08fr] gap-5">
        <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-7">
            <div class="flex items-center justify-between"><div><h2 class="text-base font-bold text-[#173b29]">Knowledge Status</h2><p class="text-xs text-[#89968e] mt-1">Distribusi status basis pengetahuan</p></div><button class="text-[#a0aba4]">•••</button></div>
            @php
                $ks = $knowledgeStatus;
                $aktifEnd = $ks['aktif_pct'];
                $draftEnd = $ks['aktif_pct'] + $ks['draft_pct'];
                $donutGradient = "conic-gradient(#176b45 0 {$aktifEnd}%, #d7a735 {$aktifEnd}% {$draftEnd}%, #dce4df {$draftEnd}% 100%)";
            @endphp
            <div class="flex items-center justify-center gap-9 py-8">
                <div class="relative w-40 h-40 rounded-full flex items-center justify-center" style="background: {{ $donutGradient }}">
                    <div class="w-24 h-24 rounded-full bg-white flex flex-col items-center justify-center">
                        <strong class="text-2xl text-[#173b29]">{{ $ks['total'] }}</strong>
                        <span class="text-[10px] text-[#9aa59e]">Total Knowledge</span>
                    </div>
                </div>
                <div class="space-y-4 text-xs">
                    <div class="flex items-center gap-3"><i class="w-2.5 h-2.5 rounded-full bg-[#176b45]"></i><span class="text-[#6c7971] w-14">Aktif</span><strong class="text-[#173b29]">{{ $ks['aktif'] }} <span class="font-normal text-[#9aa59e]">({{ $ks['aktif_pct'] }}%)</span></strong></div>
                    <div class="flex items-center gap-3"><i class="w-2.5 h-2.5 rounded-full bg-[#d7a735]"></i><span class="text-[#6c7971] w-14">Draft</span><strong class="text-[#173b29]">{{ $ks['draft'] }} <span class="font-normal text-[#9aa59e]">({{ $ks['draft_pct'] }}%)</span></strong></div>
                    <div class="flex items-center gap-3"><i class="w-2.5 h-2.5 rounded-full bg-[#dce4df]"></i><span class="text-[#6c7971] w-14">Nonaktif</span><strong class="text-[#173b29]">{{ $ks['nonaktif'] }} <span class="font-normal text-[#9aa59e]">({{ $ks['nonaktif_pct'] }}%)</span></strong></div>
                </div>
            </div>
            <div class="border-t border-[#eef3ef] pt-4 flex items-center justify-between text-xs"><span class="text-[#8b9790]">Status diperbarui hari ini</span><a href="{{ route('knowledge.publikasi.index') }}" class="font-semibold text-[#176b45] hover:underline">Kelola publikasi →</a></div>
        </section>
        <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-7"><div class="flex items-center justify-between mb-5"><div><h2 class="text-base font-bold text-[#173b29]">Perubahan Terbaru</h2><p class="text-xs text-[#89968e] mt-1">Aktivitas terakhir pada knowledge base</p></div><a href="{{ route('knowledge.riwayat.index') }}" class="text-xs font-semibold text-[#176b45]">Lihat semua →</a></div>
            <div class="space-y-4">
                @php
                    $actionIcons = [
                        'created' => ['bg' => '#e8f4ed', 'text' => '#176b45', 'icon' => 'M12 4v16m8-8H4'],
                        'updated' => ['bg' => '#fff4df', 'text' => '#b8860b', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                        'deleted' => ['bg' => '#fdeaea', 'text' => '#c53030', 'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
                        'activated' => ['bg' => '#e8f4ed', 'text' => '#176b45', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'deactivated' => ['bg' => '#f0f4f1', 'text' => '#8b9790', 'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
                    ];
                    $actionLabels = ['created' => 'menambah', 'updated' => 'mengubah', 'deleted' => 'menghapus', 'activated' => 'mengaktifkan', 'deactivated' => 'menonaktifkan'];
                @endphp
                @forelse($recentChanges as $log)
                    @php($cfg = $actionIcons[$log->action] ?? $actionIcons['updated'])
                    @php($initials = strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), explode(' ', trim($log->user_name ?? 'SY'), 2)))))
                    <div class="flex gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center" style="background:{{ $cfg['bg'] }};color:{{ $cfg['text'] }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cfg['icon'] }}"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs leading-5 text-[#526159]">{{ $log->description }}</p>
                            <p class="mt-1 text-[10px] text-[#9aa59e]">{{ $log->user_name ?? 'Sistem' }} · {{ $log->created_at?->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-[#89968e]">Belum ada perubahan terbaru.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
