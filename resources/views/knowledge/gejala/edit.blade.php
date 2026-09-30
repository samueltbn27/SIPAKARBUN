@extends('layouts.app')

@section('title', 'Edit Gejala')
@section('subtitle', 'Perbarui definisi, kriteria observasi, dan referensi gejala.')

@section('knowledge-form-width', 'standard')

@section('content')
<div class="knowledge-form-width--standard">
    <form method="POST" enctype="multipart/form-data" action="{{ route('knowledge.gejala.update', $gejala) }}" class="knowledge-form-card">
        @csrf
        @method('PUT')
        @include('knowledge.gejala._form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.gejala.show', $gejala)" submit="Simpan Perubahan" />
    </form>
</div>
@endsection
