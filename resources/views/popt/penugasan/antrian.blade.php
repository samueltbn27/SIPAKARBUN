@extends('layouts.app')

@section('title', 'Antrian Kasus')

@section('content')
<div class="space-y-6"><div><h1 class="text-2xl font-bold text-[#173b29]">Antrian Kasus</h1><p class="mt-1 text-sm text-[#77847c]">Kasus berstatus Diterima yang menunggu penugasan Operator. Bersifat baca-saja — penugasan dilakukan oleh Operator UPTD.</p></div>
<div class="overflow-hidden rounded-xl border border-[#e6eee8] bg-white"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Kasus</th><th class="px-4 py-3 text-left">Komoditas / Penyakit</th><th class="px-4 py-3 text-left">Wilayah</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Dibuat</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($kasus as $item)<tr><td class="px-4 py-3 font-mono text-xs">{{ $item->kasus_code }}</td><td class="px-4 py-3">{{ $item->komoditas_name_snapshot ?: '-' }}<br><span class="text-xs text-gray-500">{{ $item->penyakit_name_snapshot ?: '-' }}</span></td><td class="px-4 py-3 text-gray-600">{{ $item->permohonan?->kabupaten ?: '-' }}</td><td class="px-4 py-3"><x-kasus.status-badge :status="$item->current_status" /></td><td class="px-4 py-3 text-gray-600">{{ $item->created_at?->format('d-m-Y H:i') }}</td></tr>@empty<tr><td colspan="5" class="px-4 py-12 text-center text-gray-500">Tidak ada kasus dalam antrian.</td></tr>@endforelse</tbody></table></div><div class="border-t border-gray-100 px-4 py-3">{{ $kasus->withQueryString()->links() }}</div></div></div>
@endsection
