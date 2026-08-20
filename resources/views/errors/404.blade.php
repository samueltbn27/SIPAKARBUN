@auth
    @extends('layouts.app')

    @section('title', 'Halaman Tidak Ditemukan')

    @section('content')
        <div class="flex min-h-[60vh] flex-col items-center justify-center text-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
            </span>
            <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-[#173b29]">404 · Halaman Tidak Ditemukan</h1>
            <p class="mt-2 max-w-md text-sm text-[#66746c]">Halaman yang Anda tuju tidak tersedia atau telah dipindahkan.</p>
            <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#173b29]">
                Kembali ke Dashboard
            </a>
        </div>
    @endsection
@else
    <!DOCTYPE html>
    <html lang="id" class="h-full">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 · Tidak Ditemukan — SIPAKARBUN</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        @vite(['resources/css/app.css'])
    </head>
    <body class="flex h-full items-center justify-center bg-[#173b29] p-4">
        <div class="max-w-md rounded-2xl bg-white p-10 text-center">
            <h1 class="text-3xl font-extrabold tracking-tight text-[#173b29]">404 · Halaman Tidak Ditemukan</h1>
            <p class="mt-2 text-sm text-[#66746c]">Halaman yang Anda tuju tidak tersedia.</p>
            <a href="{{ route('login') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">Masuk</a>
        </div>
    </body>
    </html>
@endauth