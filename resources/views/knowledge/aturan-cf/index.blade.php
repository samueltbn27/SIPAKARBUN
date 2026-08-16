@extends('layouts.app')

@section('title', 'Aturan CF')

@section('content')
    <?php $canManage = auth()->user()?->hasRole(['admin', 'popt']); ?>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Aturan CF</h1>
                <p class="mt-1 text-sm text-gray-500">Kelola aturan certainty factor antara penyakit dan gejala.</p>
            </div>
            @if ($canManage)
            <a href="{{ route('knowledge.aturan-cf.create') }}"
               class="inline-flex items-center justify-center bg-green-600 text-white hover:bg-green-700 rounded-lg px-4 py-2 text-sm font-medium">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Aturan CF
            </a>
            @endif
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <form method="GET" action="{{ route('knowledge.aturan-cf.index') }}" class="flex flex-col sm:flex-row sm:items-end gap-4">
                <div class="flex-1">
                    <label for="penyakit_id" class="block text-sm font-medium text-gray-700 mb-1">Penyakit</label>
                    <select id="penyakit_id" name="penyakit_id"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">
                        <option value="">Semua Penyakit</option>
                        @foreach($penyakitList as $id => $nama)
                            <option value="{{ $id }}" @selected((string) request('penyakit_id') === (string) $id)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center h-[38px]">
                    <input type="checkbox" id="aktif_saja" name="aktif_saja" value="1"
                           class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                           @checked(request('aktif_saja') == '1')>
                    <label for="aktif_saja" class="ml-2 text-sm font-medium text-gray-700">Aktif saja</label>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="bg-green-600 text-white hover:bg-green-700 rounded-lg px-4 py-2 text-sm font-medium">
                        Filter
                    </button>
                    <a href="{{ route('knowledge.aturan-cf.index') }}"
                       class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Penyakit</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Gejala</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">CF Pakar</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Versi</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($aturanCf as $aturan)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $aturan->penyakit?->nama ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $aturan->gejala?->nama ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-mono">{{ number_format($aturan->cf_pakar, 3) }}</td>
                                <td class="px-4 py-3">
                                    <x-knowledge.status-badge :status="$aturan->status" />
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $aturan->version }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium space-x-2">
                                    @if ($canManage)
                                    <a href="{{ route('knowledge.aturan-cf.edit', $aturan) }}"
                                       class="text-green-600 hover:text-green-900">Edit</a>
                                    @if(auth()->user()->hasRole(['admin', 'popt']))
                                        <form method="POST" action="{{ route('knowledge.aturan-cf.destroy', $aturan) }}"
                                              class="inline"
                                              onsubmit="return confirm('Hapus aturan CF ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                        </form>
                                    @endif
                                    @else
                                    <span class="text-xs text-gray-400">Read-only</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="text-sm text-gray-500">Belum ada aturan CF yang tersimpan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-center">
            {{ $aturanCf->links() }}
        </div>
    </div>
@endsection
