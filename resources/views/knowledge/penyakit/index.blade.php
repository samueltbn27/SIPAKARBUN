@extends('layouts.app')

@section('title', 'Daftar Penyakit')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Daftar Penyakit</h1>
            <p class="mt-1 text-sm text-gray-600">Kelola data penyakit pada basis pengetahuan SIPAKARBUN.</p>
        </div>
        <a href="{{ route('knowledge.penyakit.create') }}" class="inline-flex items-center justify-center bg-green-600 text-white hover:bg-green-700 rounded-lg px-4 py-2 text-sm font-medium">
            Tambah Penyakit
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-6">
        <form method="GET" action="{{ route('knowledge.penyakit.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1">
                <label for="q" class="sr-only">Cari penyakit</label>
                <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama penyakit..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 select-none cursor-pointer">
                <input type="checkbox" name="aktif_saja" value="1" {{ request('aktif_saja') ? 'checked' : '' }}
                    class="rounded border-gray-300 text-green-600 focus:ring-green-200">
                Aktif saja
            </label>
            <button type="submit" class="inline-flex items-center justify-center bg-green-600 text-white hover:bg-green-700 rounded-lg px-4 py-2 text-sm font-medium">
                Cari
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Nama</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Deskripsi</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Komoditas</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($penyakit as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-mono text-gray-700 whitespace-nowrap">{{ $p->kode ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $p->nama }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs">
                                <span class="line-clamp-2">{{ \Illuminate\Support\Str::limit($p->deskripsi, 100) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                @if ($p->is_active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Aktif</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                @if ($p->penyakitKomoditas && $p->penyakitKomoditas->count() > 0)
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                        {{ $p->penyakitKomoditas->count() }} komoditas
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">0 komoditas</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('knowledge.penyakit.edit', $p) }}" class="inline-flex items-center bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg px-3 py-1.5 text-xs font-medium">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('knowledge.penyakit.destroy', $p) }}" onsubmit="return confirm('Yakin ingin menghapus penyakit {{ e($p->nama) }}?');" class="inline">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="inline-flex items-center bg-red-600 text-white hover:bg-red-700 rounded-lg px-3 py-1.5 text-xs font-medium">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-500">
                                    <svg class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-sm font-medium">Belum ada data penyakit.</p>
                                    <a href="{{ route('knowledge.penyakit.create') }}" class="text-sm text-green-600 hover:text-green-700 font-medium">Tambah penyakit pertama</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($penyakit->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {!! $penyakit->links() !!}
            </div>
        @endif
    </div>
</div>
@endsection
