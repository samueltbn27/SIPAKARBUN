@extends('layouts.app')

@section('title', 'Publikasi Knowledge')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Publikasi Knowledge</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola status publikasi knowledge.</p>
        </div>

        <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 text-sm text-blue-700">
            Kelola status publikasi knowledge. Hanya knowledge aktif yang dapat dikonsumsi Mahasiswa 2 untuk diagnosis.
        </div>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden" x-data="{ tab: 'penyakit' }">
            <div class="border-b border-gray-200">
                <nav class="flex flex-wrap -mb-px" aria-label="Tabs">
                    <button @click="tab = 'penyakit'"
                            :class="tab === 'penyakit' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="px-4 py-3 text-sm font-medium border-b-2">
                        Penyakit Nonaktif
                        <span class="ml-2 rounded-full px-2 py-0.5 text-xs {{ $penyakitNonaktif->isNotEmpty() ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500' }}">{{ $penyakitNonaktif->count() }}</span>
                    </button>
                    <button @click="tab = 'gejala'"
                            :class="tab === 'gejala' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="px-4 py-3 text-sm font-medium border-b-2">
                        Gejala Nonaktif
                        <span class="ml-2 rounded-full px-2 py-0.5 text-xs {{ $gejalaNonaktif->isNotEmpty() ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500' }}">{{ $gejalaNonaktif->count() }}</span>
                    </button>
                    <button @click="tab = 'aturan'"
                            :class="tab === 'aturan' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="px-4 py-3 text-sm font-medium border-b-2">
                        Aturan CF Nonaktif
                        <span class="ml-2 rounded-full px-2 py-0.5 text-xs {{ $aturanCfNonaktif->isNotEmpty() ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500' }}">{{ $aturanCfNonaktif->count() }}</span>
                    </button>
                    <button @click="tab = 'solusi'"
                            :class="tab === 'solusi' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="px-4 py-3 text-sm font-medium border-b-2">
                        Solusi Nonaktif
                        <span class="ml-2 rounded-full px-2 py-0.5 text-xs {{ $solusiNonaktif->isNotEmpty() ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500' }}">{{ $solusiNonaktif->count() }}</span>
                    </button>
                </nav>
            </div>

            <div x-show="tab === 'penyakit'" class="p-4">
                @if($penyakitNonaktif->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($penyakitNonaktif as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $item->nama }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <form method="POST" action="{{ route('knowledge.publikasi.toggle') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="model" value="Penyakit">
                                                <input type="hidden" name="id" value="{{ $item->id }}">
                                                <input type="hidden" name="is_active" value="1">
                                                <button type="submit"
                                                        class="bg-green-600 text-white hover:bg-green-700 rounded-lg px-3 py-1.5 text-xs font-medium">
                                                    Aktifkan
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex flex-col items-center py-10">
                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-gray-500">Semua data sudah aktif</p>
                    </div>
                @endif
            </div>

            <div x-show="tab === 'gejala'" x-cloak class="p-4">
                @if($gejalaNonaktif->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($gejalaNonaktif as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $item->nama }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <form method="POST" action="{{ route('knowledge.publikasi.toggle') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="model" value="Gejala">
                                                <input type="hidden" name="id" value="{{ $item->id }}">
                                                <input type="hidden" name="is_active" value="1">
                                                <button type="submit"
                                                        class="bg-green-600 text-white hover:bg-green-700 rounded-lg px-3 py-1.5 text-xs font-medium">
                                                    Aktifkan
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex flex-col items-center py-10">
                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-gray-500">Semua data sudah aktif</p>
                    </div>
                @endif
            </div>

            <div x-show="tab === 'aturan'" x-cloak class="p-4">
                @if($aturanCfNonaktif->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Penyakit</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Gejala</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">CF Pakar</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($aturanCfNonaktif as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $item->penyakit?->nama ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $item->gejala?->nama ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-mono">{{ number_format($item->cf_pakar, 3) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <form method="POST" action="{{ route('knowledge.publikasi.toggle') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="model" value="AturanCf">
                                                <input type="hidden" name="id" value="{{ $item->id }}">
                                                <input type="hidden" name="is_active" value="1">
                                                <button type="submit"
                                                        class="bg-green-600 text-white hover:bg-green-700 rounded-lg px-3 py-1.5 text-xs font-medium">
                                                    Aktifkan
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex flex-col items-center py-10">
                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-gray-500">Semua data sudah aktif</p>
                    </div>
                @endif
            </div>

            <div x-show="tab === 'solusi'" x-cloak class="p-4">
                @if($solusiNonaktif->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($solusiNonaktif as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $item->nama ?? $item->judul ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <form method="POST" action="{{ route('knowledge.publikasi.toggle') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="model" value="Solusi">
                                                <input type="hidden" name="id" value="{{ $item->id }}">
                                                <input type="hidden" name="is_active" value="1">
                                                <button type="submit"
                                                        class="bg-green-600 text-white hover:bg-green-700 rounded-lg px-3 py-1.5 text-xs font-medium">
                                                    Aktifkan
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex flex-col items-center py-10">
                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-gray-500">Semua data sudah aktif</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
