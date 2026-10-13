@extends('layouts.app')

@section('title', 'Penugasan Saya')
@section('subtitle', 'Kasus yang sedang dan pernah Anda tangani.')

@php
    $statusLabels = app(\App\Services\MonitoringStatusService::class)->labels();
@endphp

@section('content')
<div class="space-y-6">
    <form method="GET" class="flex flex-col gap-3 rounded-xl border border-[#e6eee8] bg-white p-4 sm:flex-row sm:items-end">
        <label class="w-full text-xs font-semibold text-[#66746c] sm:w-auto">Status
            <select name="status" class="mt-1 w-full rounded-lg border-gray-300 text-sm sm:w-auto">
                <option value="">Semua status</option>
                @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button class="w-full rounded-lg bg-action-primary px-4 py-2 text-sm font-semibold text-white sm:w-auto">Filter</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-[#e6eee8] bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Kasus</th>
                        <th class="px-4 py-3 text-left">Poktan</th>
                        <th class="px-4 py-3 text-left">Komoditas / Penyakit</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Target Penyelesaian</th>
                        <th class="px-4 py-3 text-left">Status Waktu</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($kasus as $item)
                        @php($latestAssignment = $item->penugasanPopt->sortByDesc('assigned_at')->first())
                        <tr class="align-top">
                            <td class="px-4 py-3 font-mono text-xs">{{ $item->kasus_code }}</td>
                            <td class="px-4 py-3">{{ $item->permohonan?->kelompok_tani_name_snapshot ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $item->komoditas_name_snapshot ?: '-' }}<br><span class="text-xs text-gray-500">{{ $item->penyakit_name_snapshot ?: '-' }}</span></td>
                            <td class="px-4 py-3"><x-kasus.handling-status-badge :status="$monitoringStatuses[$item->id]" />@if($monitoringStatuses[$item->id]['history_note'])<p class="mt-2 text-xs text-amber-800">{{ $monitoringStatuses[$item->id]['history_note'] }}</p>@endif</td>
                            <td class="px-4 py-3">{{ $monitoringStatuses[$item->id]['effective_deadline_at']?->format('d-m-Y H:i') ?? 'Belum ditentukan' }}</td>
                            <td class="px-4 py-3">{{ $monitoringStatuses[$item->id]['history_incomplete'] ? 'Perlu tinjauan data historis' : ($monitoringStatuses[$item->id]['is_overdue'] ?? false ? 'Melewati Target Waktu' : (($monitoringStatuses[$item->id]['label'] ?? null) === 'Selesai' ? 'Selesai' : ($monitoringStatuses[$item->id]['effective_deadline_at'] ? 'Tepat Waktu' : 'Target belum tersedia'))) }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('popt.penugasan.show', $item->id) }}" class="font-semibold text-[#176b45]">Lihat Penugasan</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">Belum ada penugasan atau riwayat kasus.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-4 py-3">{{ $kasus->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
