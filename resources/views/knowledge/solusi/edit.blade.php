@extends('layouts.app')

@section('title', 'Edit Solusi')
@section('subtitle', 'Perbarui rekomendasi penanganan penyakit.')

@section('content')
<div class="mx-auto w-full max-w-4xl">
    <form method="POST" action="{{ route('knowledge.solusi.update', $solusi) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('knowledge.solusi._form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.solusi.index')" submit="Simpan Perubahan" />
    </form>
</div>
@endsection
