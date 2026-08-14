@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('subtitle', 'Kelola akun pengguna dan persetujuan registrasi')

@section('content')
<div class="max-w-[1500px] mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-[#8c9890] mb-2"><span>Pengaturan</span><span>/</span><span class="text-[#176b45]">Pengguna</span></div>
            <h1 class="text-2xl font-bold tracking-tight text-[#173b29]">Manajemen Pengguna</h1>
            <p class="mt-1 text-sm text-[#77847c]">Kelola akun pengguna dan setujui permohonan registrasi.</p>
        </div>
        @if($pendingCount > 0)
        <a href="{{ route('knowledge.pengguna.index', ['status' => 'pending']) }}" class="inline-flex items-center gap-2 rounded-lg bg-[#fff4df] border border-[#f0d98a] px-4 py-2.5 text-sm font-semibold text-[#b8860b] hover:bg-[#fef0d0] transition">
            <span class="flex items-center justify-center w-5 h-5 rounded-full bg-[#d7a735] text-white text-xs font-bold">{{ $pendingCount }}</span>
            {{ $pendingCount }} akun menunggu persetujuan
        </a>
        @endif
    </div>

    {{-- Toolbar --}}
    <div class="soft-card rounded-xl border border-[#e6eee8] bg-white p-4 sm:p-5">
        <form method="GET" action="{{ route('knowledge.pengguna.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#9aa59e]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama, email, atau no. HP..."
                       class="w-full rounded-lg border border-[#d6e0d9] pl-9 pr-3 py-2.5 text-sm outline-none transition focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15">
            </div>
            <select name="status" class="rounded-lg border border-[#d6e0d9] px-3 py-2.5 text-sm outline-none transition focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15 bg-white">
                <option value="">Semua Status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="pending" @selected(request('status') === 'pending')>Menunggu Persetujuan</option>
            </select>
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#115a39] transition">
                Filter
            </button>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="soft-card rounded-xl border border-[#e6eee8] bg-white overflow-hidden">
        @if($users->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#eef3ef]">
                <thead>
                    <tr class="bg-[#f7faf8]">
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">No. HP</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Terdaftar</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-[#8b9790] uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0f4f1] bg-white">
                    @foreach($users as $user)
                    @php($roleName = $user->roles->first()?->name ?? '-')
                    @php($roleLabels = ['admin' => 'Admin', 'pakar' => 'Pakar', 'operator_uptd' => 'Operator UPTD', 'popt' => 'POPT'])
                    @php($roleLabel = $roleLabels[$roleName] ?? ucfirst($roleName))
                    @php($initials = strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), explode(' ', trim($user->name), 2)))))
                    <tr class="hover:bg-[#f7faf8] transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-9 h-9 rounded-full bg-[#dcefe3] text-xs font-bold text-[#176b45] flex-shrink-0">{{ $initials }}</span>
                                <div>
                                    <div class="text-sm font-semibold text-[#173b29]">{{ $user->name }}</div>
                                    @if($user->id === auth()->id())
                                        <span class="text-[10px] text-[#176b45] font-medium">Anda</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-[#526159]">{{ $user->email }}</td>
                        <td class="px-6 py-4 text-sm text-[#526159]">{{ $user->phone ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full bg-[#eef5f7] px-2.5 py-0.5 text-xs font-medium text-[#3d6b78]">{{ $roleLabel }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->is_active)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#e8f4ed] px-2.5 py-0.5 text-xs font-semibold text-[#176b45]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#176b45]"></span> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#fff4df] px-2.5 py-0.5 text-xs font-semibold text-[#b8860b]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#d7a735]"></span> Menunggu
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-[#8b9790] whitespace-nowrap">{{ $user->created_at?->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="inline-flex items-center gap-2">
                                @if(!$user->is_active)
                                    {{-- Approve --}}
                                    <form method="POST" action="{{ route('knowledge.pengguna.approve', $user) }}" onsubmit="return confirm('Setujui akun {{ e($user->name) }}? Akun ini akan dapat digunakan untuk login.');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-[#176b45] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#115a39] transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Setujui
                                        </button>
                                    </form>
                                    {{-- Reject --}}
                                    <form method="POST" action="{{ route('knowledge.pengguna.reject', $user) }}" onsubmit="return confirm('Tolak dan hapus akun {{ e($user->name) }}? Tindakan ini tidak dapat dibatalkan.');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-[#e4d4d4] bg-white px-3 py-1.5 text-xs font-semibold text-[#c53030] hover:bg-[#fdeaea] transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Tolak
                                        </button>
                                    </form>
                                @else
                                    {{-- Toggle active/inactive --}}
                                    <form method="POST" action="{{ route('knowledge.pengguna.toggle', $user) }}" onsubmit="return confirm('{{ $user->id === auth()->id() ? 'Nonaktifkan akun Anda sendiri? Anda tidak akan bisa login.' : 'Nonaktifkan akun ' . e($user->name) . '?' }}');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center rounded-lg border border-[#e4ece7] bg-white px-3 py-1.5 text-xs font-medium text-[#8b9790] hover:bg-[#f3f8f4] transition" @disabled($user->id === auth()->id() && $user->is_active)>
                                            Nonaktifkan
                                        </button>
                                    </form>
                                @endif

                                {{-- Delete (except self) --}}
                                @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('knowledge.pengguna.destroy', $user) }}" onsubmit="return confirm('Hapus akun {{ e($user->name) }} permanen? Tindakan ini tidak dapat dibatalkan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-[#e4d4d4] bg-white p-1.5 text-[#c53030] hover:bg-[#fdeaea] transition" title="Hapus permanen">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-16">
            <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#f3f8f4] text-[#b0c4ba] mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </span>
            <p class="text-sm font-medium text-[#8b9790]">Belum ada pengguna terdaftar.</p>
            @if(request('status') === 'pending')
            <p class="text-xs text-[#b0bab3] mt-1">Tidak ada akun yang menunggu persetujuan.</p>
            @endif
        </div>
        @endif

        @if($users->hasPages())
        <div class="px-6 py-3 border-t border-[#eef3ef] bg-[#f7faf8]">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
