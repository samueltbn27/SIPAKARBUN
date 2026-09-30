@extends('layouts.app')

@section('title', 'Edit Gejala')
@section('subtitle', 'Perbarui definisi, kriteria observasi, dan referensi gejala.')

@section('content')
<div class="mx-auto w-full max-w-4xl">
    <form method="POST" enctype="multipart/form-data" action="{{ route('knowledge.gejala.update', $gejala) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('knowledge.gejala._form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.gejala.show', $gejala)" submit="Simpan Perubahan" />
    </form>
</div>
@endsection
