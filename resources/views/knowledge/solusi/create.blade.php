@extends('layouts.app')

@section('title', 'Tambah Solusi')
@section('subtitle', 'Tambahkan rekomendasi penanganan penyakit.')

@section('content')
<div class="mx-auto w-full max-w-4xl">
    <form method="POST" action="{{ route('knowledge.solusi.store') }}" class="space-y-6">
        @csrf
        @include('knowledge.solusi._form-fields')
        <x-knowledge.form-actions :cancel="route('knowledge.solusi.index')" submit="Simpan" />
    </form>
</div>
@endsection
