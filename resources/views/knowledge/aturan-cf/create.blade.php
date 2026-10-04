@extends('layouts.app')

@section('title', 'Tambah Aturan Penyakit')
@section('subtitle', 'Tambahkan gejala pendukung dan nilai CF baku untuk penyakit.')

@section('knowledge-form-width', 'complex')

@section('content')
<div class="knowledge-form-width--complex">
    <form method="POST" action="{{ route('knowledge.aturan-cf.store') }}" class="knowledge-form-card">
        @csrf
        @include('knowledge.aturan-cf._batch-form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.aturan-cf.index')" submit="Simpan Semua Aturan" />
    </form>
</div>
@endsection
