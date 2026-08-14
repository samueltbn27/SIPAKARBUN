@extends('layouts.app')

@section('title', 'Tambah Penyakit')

@section('content')
<div class="space-y-6 max-w-4xl">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Tambah Penyakit</h1>
            <p class="mt-1 text-sm text-gray-600">Buat data penyakit baru beserta relasi komoditasnya.</p>
        </div>
        <a href="{{ route('knowledge.penyakit.index') }}" class="inline-flex items-center justify-center bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg px-4 py-2 text-sm font-medium">
            Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            <p class="font-medium mb-1">Terdapat kesalahan pada input:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('knowledge.penyakit.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="kode" class="block text-sm font-medium text-gray-700">Kode <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <input type="text" id="kode" name="kode" value="{{ old('kode') }}"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">
                    @if ($errors->has('kode'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('kode') }}</p>
                    @endif
                </div>
                <div>
                    <label for="nama" class="block text-sm font-medium text-gray-700">Nama <span class="text-red-500">*</span></label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">
                    @if ($errors->has('nama'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('nama') }}</p>
                    @endif
                </div>
            </div>

            <div>
                <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" rows="4"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">{{ old('deskripsi') }}</textarea>
                @if ($errors->has('deskripsi'))
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('deskripsi') }}</p>
                @endif
            </div>

            <div>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 select-none cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                        class="rounded border-gray-300 text-green-600 focus:ring-green-200">
                    Aktif
                </label>
                @if ($errors->has('is_active'))
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('is_active') }}</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-6">
            <h2 class="text-sm font-semibold text-gray-900">Komoditas Terkait</h2>
            <p class="mt-0.5 text-xs text-gray-500">Pilih komoditas yang dapat terkena penyakit ini.</p>
            @if (!empty($komoditas))
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($komoditas as $k)
                        <label class="inline-flex items-start gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 select-none">
                            <input type="checkbox" name="komoditas_id[]" value="{{ $k['id'] }}"
                                {{ in_array((string) $k['id'], array_map('strval', old('komoditas_id', []))) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-green-600 focus:ring-green-200">
                            <span>
                                <span class="font-mono text-xs text-gray-500">{{ $k['kode'] }}</span>
                                <span class="block font-medium">{{ $k['nama'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @else
                <p class="mt-4 text-sm text-gray-500">Belum ada komoditas yang terdaftar.</p>
            @endif
            @if ($errors->has('komoditas_id'))
                <p class="mt-2 text-xs text-red-600">{{ $errors->first('komoditas_id') }}</p>
            @endif
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('knowledge.penyakit.index') }}" class="inline-flex items-center justify-center bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg px-4 py-2 text-sm font-medium">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center justify-center bg-green-600 text-white hover:bg-green-700 rounded-lg px-4 py-2 text-sm font-medium">
                Simpan
            </button>
        </div>
    </form>
</div>
@endsection
