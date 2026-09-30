@extends('layouts.app')

@section('title', 'Edit Penyakit')
@section('subtitle', 'Perbarui data penyakit dan komoditas terkait.')

@section('knowledge-form-width', 'standard')

@section('content')
<div class="knowledge-form-width--standard">
    <form method="POST" enctype="multipart/form-data" action="{{ route('knowledge.penyakit.update', $penyakit) }}" class="knowledge-form-card">
        @csrf
        @method('PUT')
        @include('knowledge.penyakit._form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.penyakit.index')" submit="Simpan Perubahan" />
    </form>
</div>
@endsection
