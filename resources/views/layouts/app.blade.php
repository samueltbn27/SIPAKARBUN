<!DOCTYPE html>
<html lang="id" class="h-full bg-[#f7faf8]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', 'Dashboard') — SIPAKARBUN</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
    {{-- Global loading state — tampil hanya saat halaman pertama kali dimuat. --}}
    <div x-data="{ loading: document.readyState === 'loading' }"
         x-init="window.addEventListener('load', () => loading = false)"
         x-show="loading"
         x-transition.opacity.duration.200ms
         class="fixed inset-0 z-[100] flex items-center justify-center bg-[#f7faf8]/70 backdrop-blur-sm"
         aria-busy="true" role="status">
        <div class="flex flex-col items-center gap-3">
            <span class="h-10 w-10 animate-spin rounded-full border-4 border-[#e4ece7] border-t-[#176b45]"></span>
            <span class="text-xs font-semibold text-[#66746c]">Memuat SIPAKARBUN…</span>
        </div>
    </div>

    <div class="min-h-full flex" x-data="{ sidebarOpen: false }">
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 lg:pl-64">
            @include('layouts.partials.topbar')

            <main class="flex-1 overflow-y-auto bg-[#f7faf8] p-4 sm:p-6 lg:p-8">
                @if (session('success'))
                    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700" x-data="{ show: true }" x-show="show" x-transition>
                        <div class="flex items-center justify-between gap-4">
                            <span>{{ session('success') }}</span>
                            <button type="button" @click="show = false" class="text-green-500 hover:text-green-700">&times;</button>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700" x-data="{ show: true }" x-show="show" x-transition>
                        <div class="flex items-center justify-between gap-4">
                            <span>{{ session('error') }}</span>
                            <button type="button" @click="show = false" class="text-red-500 hover:text-red-700">&times;</button>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
