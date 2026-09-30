@extends('layouts.app')

@section('title', 'Tambah Gejala')
@section('subtitle', 'Tambahkan definisi, kriteria observasi, dan referensi gejala.')

@section('content')
<div class="mx-auto w-full max-w-4xl">
    <form method="POST" enctype="multipart/form-data" action="{{ route('knowledge.gejala.store') }}" class="space-y-6">
        @csrf
        @include('knowledge.gejala._form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.gejala.index')" submit="Simpan" />
    </form>
</div>
@endsection
