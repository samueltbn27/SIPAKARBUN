@extends('layouts.app')

@section('title', 'Edit Aturan Penyakit')
@section('subtitle', 'Perbarui hubungan gejala dan penyakit dengan nilai CF baku.')

@section('content')
<div class="mx-auto w-full max-w-6xl">
    <form method="POST" action="{{ route('knowledge.aturan-cf.update', $aturanCf) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('knowledge.aturan-cf._form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.aturan-cf.show', $aturanCf)" submit="Simpan Perubahan" />
    </form>
</div>
@endsection
