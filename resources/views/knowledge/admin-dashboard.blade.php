@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('subtitle', 'Pantau sistem, persetujuan akun, dan aktivitas')

@section('content')
<?php
    $actionIcons = [
        'created' => ['bg' => '#e8f4ed', 'text' => '#176b45', 'icon' => 'M12 4v16m8-8H4'],
        'updated' => ['bg' => '#fff4df', 'text' => '#b8860b', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
        'deleted' => ['bg' => '#fdeaea', 'text' => '#c53030', 'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
        'activated' => ['bg' => '#e8f4ed', 'text' => '#176b45', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'deactivated' => ['bg' => '#f0f4f1', 'text' => '#8b9790', 'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
    ];
    $roleLabels = ['admin'=>'Admin','popt'=>'POPT','operator_uptd'=>'Operator (OP)'];
    $roleData = [
        'admin' => ['label' => 'Admin', 'color' => '#7c3aed'],
        'popt' => ['label' => 'POPT', 'color' => '#176b45'],
        'operator_uptd' => ['label' => 'Operator (OP)', 'color' => '#3d6b78'],
    ];
?>
<div class="max-w-[1500px] mx-auto space-y-7">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-[#8c9890] mb-2"><span>Admin Panel</span><span>/</span><span class="text-[#176b45]">Dashboard</span></div>
            <h1 class="text-2xl sm:text-[28px] font-bold tracking-tight text-[#173b29]">Selamat datang, {{ auth()->user()?->name }} <span class="text-xl">👋</span></h1>
            <p class="mt-1 text-sm text-[#77847c]">Kelola pengguna, persetujuan akun, dan pantau aktivitas sistem.</p>
        </div>
        @if($userStats['pending'] > 0)
        <a href="{{ route('knowledge.pengguna.index', ['status' => 'pending']) }}" class="inline-flex items-center gap-2 rounded-lg bg-[#fff4df] border border-[#f0d98a] px-4 py-2.5 text-sm font-semibold text-[#b8860b] hover:bg-[#fef0d0] transition">
            <span class="flex items-center justify-center w-5 h-5 rounded-full bg-[#d7a735] text-white text-xs font-bold">{{ $userStats['pending'] }}</span>
            {{ $userStats['pending'] }} akun menunggu
        </a>
        @endif
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('knowledge.pengguna.index') }}" class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 hover:-translate-y-0.5 transition">
            <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-[#eef5f7]"><svg class="w-5 h-5 text-[#3d6b78]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg></span>
            <div class="mt-5 text-sm font-medium text-[#748179]">Total Pengguna</div>
            <div class="mt-1 flex items-end gap-2"><span class="text-2xl font-bold text-[#173b29]">{{ $userStats['total'] }}</span><span class="mb-1 text-[11px] font-semibold text-[#29905d]">{{ $userStats['active'] }} aktif</span></div>
        </a>
        <a href="{{ route('knowledge.pengguna.index', ['status' => 'pending']) }}" class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 hover:-translate-y-0.5 transition">
            <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-[#fff4df]"><svg class="w-5 h-5 text-[#b8860b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
            <div class="mt-5 text-sm font-medium text-[#748179]">Menunggu Approval</div>
            <div class="mt-1 flex items-end gap-2"><span class="text-2xl font-bold text-[#173b29]">{{ $userStats['pending'] }}</span><span class="mb-1 text-[11px] font-semibold text-[#b8860b]">perlu ditinjau</span></div>
        </a>
        <div class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
            <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-[#e8f4ed]"><svg class="w-5 h-5 text-[#176b45]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
            <div class="mt-5 text-sm font-medium text-[#748179]">Basis Pengetahuan</div>
            <div class="mt-1 flex items-end gap-2"><span class="text-2xl font-bold text-[#173b29]">{{ $knowledgeStatus['total'] }}</span><span class="mb-1 text-[11px] font-semibold text-[#29905d]">{{ $knowledgeStatus['aktif'] }} aktif</span></div>
        </div>
        <div class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
            <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-[#f2edf8]"><svg class="w-5 h-5 text-[#7c3aed]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></span>
            <div class="mt-5 text-sm font-medium text-[#748179]">Aktivitas Sistem</div>
            <div class="mt-1 flex items-end gap-2"><span class="text-2xl font-bold text-[#173b29]">{{ \App\Models\ActivityLog::count() }}</span><span class="mb-1 text-[11px] font-semibold text-[#7c3aed]">audit log</span></div>
        </div>
    </div>

    {{-- Persetujuan Akun + Audit Log --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-7">
            <div class="flex items-center justify-between mb-5">
                <div><h2 class="text-base font-bold text-[#173b29]">Persetujuan Akun</h2><p class="text-xs text-[#89968e] mt-1">Akun baru yang menunggu approval</p></div>
                <a href="{{ route('knowledge.pengguna.index', ['status' => 'pending']) }}" class="text-xs font-semibold text-[#176b45] hover:underline">Lihat semua →</a>
            </div>
            @if($pendingUsers->isNotEmpty())
            <div class="space-y-3">
                @foreach($pendingUsers as $user)
                <?php
                    $rName = $user->roles->first()?->name ?? '-';
                    $rLabel = $roleLabels[$rName] ?? $rName;
                    $ini = strtoupper(implode('', array_map(fn($w) => substr($w,0,1), explode(' ', trim($user->name), 2))));
                ?>
                <div class="flex items-center gap-3 p-3 rounded-lg border border-[#eef3ef] hover:bg-[#f7faf8] transition">
                    <span class="flex items-center justify-center w-10 h-10 rounded-full bg-[#fff4df] text-xs font-bold text-[#b8860b] flex-shrink-0">{{ $ini }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-[#173b29] truncate">{{ $user->name }}</div>
                        <div class="text-xs text-[#8b9790] truncate">{{ $user->email }} · {{ $rLabel }}</div>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <form method="POST" action="{{ route('knowledge.pengguna.approve', $user) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#176b45] px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-[#115a39] transition" title="Setujui">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('knowledge.pengguna.reject', $user) }}" onsubmit="return confirm('Tolak dan hapus akun {{ e($user->name) }}?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-[#e4d4d4] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#c53030] hover:bg-[#fdeaea] transition" title="Tolak & Hapus">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-10">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-[#f3f8f4] text-[#b0c4ba] mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <p class="text-sm font-medium text-[#8b9790]">Tidak ada akun menunggu approval</p>
            </div>
            @endif
        </section>

        <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-7">
            <div class="flex items-center justify-between mb-5">
                <div><h2 class="text-base font-bold text-[#173b29]">Audit Log Sistem</h2><p class="text-xs text-[#89968e] mt-1">Riwayat perubahan terbaru</p></div>
                <a href="{{ route('knowledge.riwayat.index') }}" class="text-xs font-semibold text-[#176b45] hover:underline">Lihat semua →</a>
            </div>
            <div class="space-y-3">
                @forelse($adminLogs as $log)
                <?php $cfg = $actionIcons[$log->action] ?? $actionIcons['updated']; ?>
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
                <p class="py-8 text-center text-sm text-[#89968e]">Belum ada aktivitas tercatat.</p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Manajemen User + Distribusi Role --}}
    <div class="grid grid-cols-1 xl:grid-cols-[1.4fr_1fr] gap-5">
        {{-- Manajemen User --}}
        <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-7">
            <div class="flex items-center justify-between mb-5">
                <div><h2 class="text-base font-bold text-[#173b29]">Manajemen Pengguna</h2><p class="text-xs text-[#89968e] mt-1">Daftar pengguna terbaru — kelola dari sini</p></div>
                <a href="{{ route('knowledge.pengguna.index') }}" class="text-xs font-semibold text-[#176b45] hover:underline">Kelola semua →</a>
            </div>
            <div class="space-y-2">
                @foreach($recentUsers as $user)
                <?php
                    $rName = $user->roles->first()?->name ?? '-';
                    $rLabel = $roleLabels[$rName] ?? $rName;
                    $ini = strtoupper(implode('', array_map(fn($w) => substr($w,0,1), explode(' ', trim($user->name), 2))));
                    $isSelf = $user->id === auth()->id();
                ?>
                <div class="flex items-center gap-3 p-3 rounded-lg border border-[#eef3ef] hover:bg-[#f7faf8] transition">
                    <span class="flex items-center justify-center w-9 h-9 rounded-full {{ $user->is_active ? 'bg-[#dcefe3]' : 'bg-[#fff4df]' }} text-xs font-bold {{ $user->is_active ? 'text-[#176b45]' : 'text-[#b8860b]' }} flex-shrink-0">{{ $ini }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-[#173b29] truncate">{{ $user->name }}</span>
                            @if($isSelf)<span class="text-[10px] text-[#176b45] font-medium">(Anda)</span>@endif
                        </div>
                        <div class="text-xs text-[#8b9790] truncate">{{ $user->email }} · {{ $rLabel }}</div>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        @if($user->is_active)
                            <span class="inline-flex items-center gap-1 rounded-full bg-[#e8f4ed] px-2 py-0.5 text-[10px] font-semibold text-[#176b45]">Aktif</span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-[#fff4df] px-2 py-0.5 text-[10px] font-semibold text-[#b8860b]">Menunggu</span>
                        @endif

                        @if(!$isSelf)
                            {{-- Toggle active/inactive --}}
                            <form method="POST" action="{{ route('knowledge.pengguna.toggle', $user) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-[#e4ece7] bg-white p-1.5 text-[#8b9790] hover:bg-[#f3f8f4] transition" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    @if($user->is_active)
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    @else
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @endif
                                </button>
                            </form>
                            {{-- Delete --}}
                            <form method="POST" action="{{ route('knowledge.pengguna.destroy', $user) }}" onsubmit="return confirm('Hapus akun {{ e($user->name) }} permanen? Tindakan ini tidak dapat dibatalkan.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-[#e4d4d4] bg-white p-1.5 text-[#c53030] hover:bg-[#fdeaea] transition" title="Hapus permanen">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        {{-- Distribusi Role --}}
        <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-7">
            <div class="flex items-center justify-between mb-5">
                <div><h2 class="text-base font-bold text-[#173b29]">Distribusi Pengguna</h2><p class="text-xs text-[#89968e] mt-1">Jumlah pengguna per role</p></div>
            </div>
            <div class="space-y-4">
                @foreach($roleData as $key => $info)
                <?php
                    $count = $roleBreakdown[$key] ?? 0;
                    $pct = $userStats['total'] > 0 ? round(($count / $userStats['total']) * 100) : 0;
                ?>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $info['color'] }}"></span>
                            <span class="text-sm font-medium text-[#526159]">{{ $info['label'] }}</span>
                        </div>
                        <span class="text-sm font-bold text-[#173b29]">{{ $count }} <span class="text-[10px] font-normal text-[#9aa59e]">({{ $pct }}%)</span></span>
                    </div>
                    <div class="h-2 rounded-full bg-[#f0f4f1] overflow-hidden">
                        <div class="h-full rounded-full transition-all" style="width:{{ $pct }}%;background:{{ $info['color'] }}"></div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="border-t border-[#eef3ef] pt-4 mt-5">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-[#77847c]">Total Pengguna</span>
                    <span class="font-bold text-[#173b29]">{{ $userStats['total'] }}</span>
                </div>
                <div class="flex items-center justify-between text-sm mt-2">
                    <span class="text-[#77847c]">Aktif</span>
                    <span class="font-bold text-[#176b45]">{{ $userStats['active'] }}</span>
                </div>
                <div class="flex items-center justify-between text-sm mt-2">
                    <span class="text-[#77847c]">Menunggu</span>
                    <span class="font-bold text-[#b8860b]">{{ $userStats['pending'] }}</span>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
