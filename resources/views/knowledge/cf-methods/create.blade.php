@extends('layouts.app')
@section('title', 'Tambah Metode CF')
@section('subtitle', 'Definisikan metode, skala, dan referensi metodologi CF.')
@section('content')<div class="mx-auto max-w-4xl"><form method="POST" action="{{ route('knowledge.cf-methods.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-5 sm:p-6">@csrf@include('knowledge.cf-methods._form')<div class="flex gap-3"><button class="rounded-lg bg-[#176b45] px-4 py-2 text-sm font-semibold text-white">Simpan</button><a href="{{ route('knowledge.cf-methods.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Batal</a></div></form></div>@endsection
