@extends('layouts.app')

@section('title', 'Metode CF')
@section('subtitle', 'Metode yang digunakan untuk memperoleh dan memetakan nilai Certainty Factor.')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="rounded-lg border border-[#d6ebe0] bg-[#f7fcf9] px-4 py-3 text-sm text-[#315e47]">Metode yang sudah digunakan aturan aktif sebaiknya dibuat sebagai versi baru agar histori tidak berubah.</div>
        @if(auth()->user()?->hasRole('admin'))<a href="{{ route('knowledge.cf-methods.create') }}" class="rounded-lg bg-[#176b45] px-4 py-2 text-sm font-semibold text-white hover:bg-[#115a39]">Tambah Metode</a>@endif
    </div>
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Metode</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Versi</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Skala</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Aturan</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
            @forelse($methods as $method)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3"><div class="font-semibold text-gray-900">{{ $method->name }}</div><div class="mt-1 max-w-xl text-xs text-gray-500">{{ $method->description ?: 'Belum tersedia' }}</div></td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $method->version }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ count($method->scaleOptions()) }} tingkat</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $method->aturan_cf_count }}</td>
                    <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $method->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">{{ $method->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="px-4 py-3 text-right text-sm"><a class="font-semibold text-[#176b45] hover:underline" href="{{ route('knowledge.cf-methods.show', $method) }}">Detail</a> @if(auth()->user()?->hasRole('admin'))<a class="ml-3 font-semibold text-gray-700 hover:underline" href="{{ route('knowledge.cf-methods.edit', $method) }}">Edit</a>@endif</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">Belum ada metode CF.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $methods->links() }}
</div>
@endsection
