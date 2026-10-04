@extends('layouts.app')

@section('title', 'Edit Aturan Penyakit')
@section('subtitle', 'Perbarui hubungan gejala dan penyakit dengan nilai CF baku.')

@section('knowledge-form-width', 'complex')

@section('content')
<div class="knowledge-form-width--complex">
    <form method="POST" action="{{ route('knowledge.aturan-cf.update', $aturanCf) }}" class="knowledge-form-card">
        @csrf
        @method('PUT')
        @include('knowledge.aturan-cf._form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.aturan-cf.show', $aturanCf)" submit="Simpan Perubahan" />
    </form>
</div>
@endsection
