@extends('layouts.app')

@section('title', 'Tambah Aturan CF')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tambah Aturan CF</h1>
            <p class="mt-1 text-sm text-gray-500">Tambahkan aturan certainty factor baru.</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-2xl">
            <form method="POST" action="{{ route('knowledge.aturan-cf.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="penyakit_id" class="block text-sm font-medium text-gray-700 mb-1">Penyakit <span class="text-red-500">*</span></label>
                    <select id="penyakit_id" name="penyakit_id" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">
                        <option value="">— Pilih Penyakit —</option>
                        @foreach($penyakitList as $id => $nama)
                            <option value="{{ $id }}" @selected(old('penyakit_id') == $id)>{{ $nama }}</option>
                        @endforeach
                    </select>
                    @error('penyakit_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="gejala_id" class="block text-sm font-medium text-gray-700 mb-1">Gejala <span class="text-red-500">*</span></label>
                    <select id="gejala_id" name="gejala_id" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">
                        <option value="">— Pilih Gejala —</option>
                        @foreach($gejalaList as $id => $nama)
                            <option value="{{ $id }}" @selected(old('gejala_id') == $id)>{{ $nama }}</option>
                        @endforeach
                    </select>
                    @error('gejala_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="cf_pakar" class="block text-sm font-medium text-gray-700 mb-1">CF Pakar <span class="text-red-500">*</span></label>
                    <input type="number" id="cf_pakar" name="cf_pakar" step="0.001" min="-1" max="1" required
                           value="{{ old('cf_pakar') }}"
                           placeholder="0.000"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">
                    <p class="mt-1 text-xs text-gray-500">Nilai CF harus di antara -1 dan 1</p>
                    @error('cf_pakar')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-knowledge.status-select name="status" default="draft" />

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="bg-green-600 text-white hover:bg-green-700 rounded-lg px-4 py-2 text-sm font-medium">
                        Simpan
                    </button>
                    <a href="{{ route('knowledge.aturan-cf.index') }}"
                       class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
