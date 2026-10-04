@extends('layouts.app')

@section('title', 'Tambah Solusi')
@section('subtitle', 'Tambahkan rekomendasi penanganan penyakit.')

@section('knowledge-form-width', 'standard')

@section('content')
<div class="knowledge-form-width--standard">
    <form method="POST" action="{{ route('knowledge.solusi.store') }}" class="knowledge-form-card">
        @csrf
        @include('knowledge.solusi._form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.solusi.index')" submit="Simpan" />
    </form>
</div>
@endsection
