@extends('layouts.app')

@section('title', 'Riwayat Perubahan')
@section('subtitle', 'Audit log seluruh aktivitas knowledge base')

@section('content')
<div class="max-w-[1500px] mx-auto space-y-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-[#8c9890] mb-2"><span>Knowledge</span><span>/</span><span class="text-[#176b45]">Riwayat Perubahan</span></div>
        <h1 class="text-2xl font-bold tracking-tight text-[#173b29]">Riwayat Perubahan</h1>
        <p class="mt-1 text-sm text-[#77847c]">Pelacakan seluruh aktivitas pada basis pengetahuan SIPAKARBUN.</p>
    </div>

    <div class="soft-card rounded-xl border border-[#e6eee8] bg-white overflow-hidden">
        @php
            $actionStyles = [
                'created' => ['bg' => '#e8f4ed', 'text' => '#176b45', 'label' => 'Dibuat'],
                'updated' => ['bg' => '#fff4df', 'text' => '#b8860b', 'label' => 'Diubah'],
                'deleted' => ['bg' => '#fdeaea', 'text' => '#c53030', 'label' => 'Dihapus'],
                'activated' => ['bg' => '#e8f4ed', 'text' => '#176b45', 'label' => 'Diaktifkan'],
                'deactivated' => ['bg' => '#f0f4f1', 'text' => '#8b9790', 'label' => 'Dinonaktifkan'],
            ];
        @endphp

        @if($riwayat->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#eef3ef]">
                <thead>
                    <tr class="bg-[#f7faf8]">
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Waktu</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Aksi</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Entity</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Deskripsi</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Perubahan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0f4f1] bg-white">
                    @foreach($riwayat as $log)
                    @php($style = $actionStyles[$log->action] ?? $actionStyles['updated'])
                    <tr class="hover:bg-[#f7faf8] transition-colors">
                        <td class="px-6 py-4 text-sm text-[#526159] whitespace-nowrap">{{ $log->created_at?->format('d M Y H:i') }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-[#173b29] whitespace-nowrap">{{ $log->user_name ?? 'Sistem' }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold" style="background:{{ $style['bg'] }};color:{{ $style['text'] }}">{{ $style['label'] }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-[#526159] whitespace-nowrap">
                            <span class="font-medium">{{ $log->entity_type }}</span>
                            @if($log->entity_name)
                                <span class="text-[#9aa59e] block text-xs">{{ $log->entity_name }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-[#526159] max-w-md">{{ $log->description ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm font-mono text-[#526159] whitespace-nowrap">
                            @if($log->old_value || $log->new_value)
                                <span class="text-[#c53030]">{{ $log->old_value ?? '—' }}</span>
                                <span class="text-[#9aa59e] mx-1">→</span>
                                <span class="text-[#176b45]">{{ $log->new_value ?? '—' }}</span>
                            @else
                                <span class="text-[#b0bab3]">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-16">
            <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#f3f8f4] text-[#b0c4ba] mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <p class="text-sm font-medium text-[#8b9790]">Belum ada riwayat perubahan.</p>
            <p class="text-xs text-[#b0bab3] mt-1">Aktivitas CRUD akan tercatat otomatis di sini.</p>
        </div>
        @endif

        @if($riwayat->hasPages())
        <div class="px-6 py-3 border-t border-[#eef3ef] bg-[#f7faf8]">
            {{ $riwayat->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
