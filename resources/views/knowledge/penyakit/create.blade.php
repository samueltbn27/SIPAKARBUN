@extends('layouts.app')

@section('title', 'Tambah Penyakit')
@section('subtitle', 'Tambahkan data penyakit dan komoditas terkait.')

@section('content')
<div class="mx-auto w-full max-w-4xl">
    <form method="POST" enctype="multipart/form-data" action="{{ route('knowledge.penyakit.store') }}" class="space-y-6">
        @csrf
        @include('knowledge.penyakit._form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.penyakit.index')" submit="Simpan" />
    </form>
</div>
@endsection
