@extends('layouts.app')

@section('title', 'Tambah Gejala')
@section('subtitle', 'Tambahkan definisi, kriteria observasi, dan referensi gejala.')

@section('knowledge-form-width', 'standard')

@section('content')
<div class="knowledge-form-width--standard">
    <form method="POST" enctype="multipart/form-data" action="{{ route('knowledge.gejala.store') }}" class="knowledge-form-card">
        @csrf
        @include('knowledge.gejala._form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.gejala.index')" submit="Simpan" />
    </form>
</div>
@endsection
