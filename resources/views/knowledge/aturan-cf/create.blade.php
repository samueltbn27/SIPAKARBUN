@extends('layouts.app')

@section('title', 'Tambah Aturan CF')
@section('subtitle', 'Catat hubungan gejala–penyakit beserta provenance nilai CF.')

@section('content')
<div class="mx-auto max-w-4xl">
    <form method="POST" action="{{ route('knowledge.aturan-cf.store') }}" class="space-y-6">
        @csrf
        @include('knowledge.aturan-cf._form-fields')

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Simpan</button>
            <a href="{{ route('knowledge.aturan-cf.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
        </div>
    </form>
</div>
@endsection
