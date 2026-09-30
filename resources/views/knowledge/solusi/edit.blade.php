@extends('layouts.app')

@section('title', 'Edit Solusi')
@section('subtitle', 'Perbarui rekomendasi penanganan penyakit.')

@section('knowledge-form-width', 'standard')

@section('content')
<div class="knowledge-form-width--standard">
    <form method="POST" action="{{ route('knowledge.solusi.update', $solusi) }}" class="knowledge-form-card">
        @csrf
        @method('PUT')
        @include('knowledge.solusi._form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.solusi.index')" submit="Simpan Perubahan" />
    </form>
</div>
@endsection
