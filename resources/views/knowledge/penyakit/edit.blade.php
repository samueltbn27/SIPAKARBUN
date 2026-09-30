@extends('layouts.app')

@section('title', 'Edit Penyakit')
@section('subtitle', 'Perbarui data penyakit dan komoditas terkait.')

@section('content')
<div class="mx-auto w-full max-w-4xl">
    <form method="POST" enctype="multipart/form-data" action="{{ route('knowledge.penyakit.update', $penyakit) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('knowledge.penyakit._form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.penyakit.index')" submit="Simpan Perubahan" />
    </form>
</div>
@endsection
