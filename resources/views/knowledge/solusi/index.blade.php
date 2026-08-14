@extends('layouts.app')

@section('title', 'Daftar Solusi')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Daftar Solusi</h1>
            <p class="mt-1 text-sm text-gray-600">Kelola data solusi untuk setiap penyakit.</p>
        </div>
        <a href="{{ route('knowledge.solusi.create') }}" class="bg-green-600 text-white hover:bg-green-700 rounded-lg px-4 py-2 text-sm font-medium">
            Tambah Solusi
        </a>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('knowledge.solusi.index') }}" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="sm:w-64">
                <select name="penyakit_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">
                    <option value="">Semua Penyakit</option>
                    @foreach ($penyakitList as $penyakit)
                        <option value="{{ $penyakit->id }}" {{ request('penyakit_id') == $penyakit->id ? 'selected' : '' }}>{{ $penyakit->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul atau deskripsi solusi..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="aktif_saja" id="aktif_saja" value="1" {{ request('aktif_saja') ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                <label for="aktif_saja" class="text-sm text-gray-700">Aktif saja</label>
            </div>
            <button type="submit" class="bg-green-600 text-white hover:bg-green-700 rounded-lg px-4 py-2 text-sm font-medium">
                Cari
            </button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">Penyakit</th>
                    <th class="px-4 py-3">Judul</th>
                    <th class="px-4 py-3">Deskripsi</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($solusi as $s)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $s->penyakit->nama ?? '-' }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $s->judul }}</td>
                    <td class="px-4 py-3 text-gray-600">
                        {{ \Illuminate\Support\Str::limit($s->deskripsi, 60) }}
                    </td>
                    <td class="px-4 py-3">
                        @if ($s->is_active)
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                        @else
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-600">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-2">
                            <a href="{{ route('knowledge.solusi.edit', $s) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('knowledge.solusi.destroy', $s) }}" onsubmit="return confirm('Hapus solusi ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600 text-white hover:bg-red-700 rounded-lg px-3 py-1.5 text-xs font-medium">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m-6 8h6a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2z" />
                            </svg>
                            <p>Belum ada data solusi.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-center">
        {!! $solusi->links() !!}
    </div>
</div>
@endsection
