@auth
    @extends('layouts.app')

    @section('title', 'Tidak Diizinkan')

    @section('content')
        <div class="flex min-h-[60vh] flex-col items-center justify-center text-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-600">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
            </span>
            <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-[#173b29]">403 · Akses Ditolak</h1>
            <p class="mt-2 max-w-md text-sm text-[#66746c]">
                Anda tidak memiliki izin untuk membuka halaman ini. Menu yang Anda lihat menyesuaikan peran Anda saat ini.
            </p>
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
        <title>403 · Tidak Diizinkan — SIPAKARBUN</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        @vite(['resources/css/app.css'])
    </head>
    <body class="flex h-full items-center justify-center bg-[#173b29] p-4">
        <div class="max-w-md rounded-2xl bg-white p-10 text-center">
            <h1 class="text-3xl font-extrabold tracking-tight text-[#173b29]">403 · Akses Ditolak</h1>
            <p class="mt-2 text-sm text-[#66746c]">Silakan masuk dengan akun yang sesuai untuk mengakses halaman ini.</p>
            <a href="{{ route('login') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">Masuk</a>
        </div>
    </body>
    </html>
@endauth