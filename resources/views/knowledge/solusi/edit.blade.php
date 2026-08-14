@extends('layouts.app')

@section('title', 'Edit Solusi')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Solusi</h1>
            <p class="mt-1 text-sm text-gray-600">Perbarui data solusi <span class="font-medium">{{ $solusi->judul }}</span>.</p>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('knowledge.solusi.update', $solusi) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="penyakit_id" class="mb-1 block text-sm font-medium text-gray-700">Penyakit <span class="text-red-500">*</span></label>
                <select name="penyakit_id" id="penyakit_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">
                    <option value="">Pilih Penyakit</option>
                    @foreach ($penyakitList as $penyakit)
                        <option value="{{ $penyakit->id }}" {{ old('penyakit_id', $solusi->penyakit_id) == $penyakit->id ? 'selected' : '' }}>{{ $penyakit->nama }}</option>
                    @endforeach
                </select>
                @error('penyakit_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="judul" class="mb-1 block text-sm font-medium text-gray-700">Judul <span class="text-red-500">*</span></label>
                <input type="text" name="judul" id="judul" value="{{ old('judul', $solusi->judul) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">
                @error('judul')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="deskripsi" class="mb-1 block text-sm font-medium text-gray-700">Deskripsi <span class="text-red-500">*</span></label>
                <textarea name="deskripsi" id="deskripsi" rows="4" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">{{ old('deskripsi', $solusi->deskripsi) }}</textarea>
                @error('deskripsi')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $solusi->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                <label for="is_active" class="text-sm text-gray-700">Aktif</label>
                @error('is_active')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                <button type="submit" class="bg-green-600 text-white hover:bg-green-700 rounded-lg px-4 py-2 text-sm font-medium">
                    Update
                </button>
                <a href="{{ route('knowledge.solusi.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
