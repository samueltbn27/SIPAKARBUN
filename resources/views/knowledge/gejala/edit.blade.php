@extends('layouts.app')

@section('title', 'Edit Gejala')
@section('subtitle', 'Perbarui definisi operasional, kriteria observasi, dan referensi gejala.')

@section('content')
<div class="mx-auto max-w-4xl">
    <form method="POST" enctype="multipart/form-data" action="{{ route('knowledge.gejala.update', $gejala) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('knowledge.gejala._form-fields')

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Simpan Perubahan</button>
            <a href="{{ route('knowledge.gejala.show', $gejala) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Batal</a>
        </div>
    </form>
</div>
@endsection
