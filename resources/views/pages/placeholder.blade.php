@extends('layouts.app')

@section('title', $title)

@section('content')
    <x-page-header :title="$title" :subtitle="$subtitle" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => $title]]" />

    <x-card class="flex flex-col items-center justify-center px-6 py-16 text-center">
        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#e8f4ed] text-[#176b45]">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            </svg>
        </span>
        <h2 class="mt-4 text-lg font-bold text-[#173b29]">Modul sedang disiapkan</h2>
        <p class="mt-1 max-w-md text-sm text-[#66746c]">
            Menu <span class="font-semibold text-[#176b45]">{{ $title }}</span> akan tersedia pada tahap berikutnya. Sementara itu navigasi, otorisasi peran, dan kerangka aplikasi sudah aktif.
        </p>
        <a href="{{ route('dashboard') }}"
           class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#173b29]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            Kembali ke Dashboard
        </a>
    </x-card>
@endsection