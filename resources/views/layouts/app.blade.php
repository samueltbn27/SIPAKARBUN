<!DOCTYPE html>
<html lang="id" class="h-full bg-[#f5f7f3]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', 'Dashboard') — SIPAKARBUN</title>
    {{-- Keep the brand mark bounded before the compiled stylesheet is ready. --}}
    <style>
        [x-cloak] { display: none !important; }
        .brand-mark { width: 2.25rem; height: 2.25rem; flex: 0 0 2.25rem; }
        .brand-mark > svg { width: 1.25rem; height: 1.25rem; }
        /* Critical icon bounds prevent a pre-CSS 300x150 SVG flash. */
        svg:not([width]):not([height])[class~="h-3"] { width: .75rem; height: .75rem; }
        svg:not([width]):not([height])[class~="h-3.5"] { width: .875rem; height: .875rem; }
        svg:not([width]):not([height])[class~="h-4"] { width: 1rem; height: 1rem; }
        svg:not([width]):not([height])[class~="h-5"] { width: 1.25rem; height: 1.25rem; }
        svg:not([width]):not([height])[class~="h-6"] { width: 1.5rem; height: 1.5rem; }
        svg:not([width]):not([height])[class~="h-7"] { width: 1.75rem; height: 1.75rem; }
        svg:not([width]):not([height])[class~="h-8"] { width: 2rem; height: 2rem; }
        svg:not([width]):not([height])[class~="h-10"] { width: 2.5rem; height: 2.5rem; }
        svg:not([width]):not([height])[class~="h-12"] { width: 3rem; height: 3rem; }
        .sidebar-shell { position: fixed; inset: 0 auto 0 0; z-index: 50; display: flex; width: 16rem; transform: translateX(-100%); }
        .sidebar-collapse-toggle { display: none; }
        #page-loader { display: none; }
        #page-loader.is-active { display: flex; }
        .page-loader__spinner { width: 2.5rem; height: 2.5rem; border: 4px solid #dce9df; border-top-color: #176b45; border-radius: 9999px; animation: sipakarbun-spin .8s linear infinite; }
        @keyframes sipakarbun-spin { to { transform: rotate(360deg); } }
        @media (min-width: 1024px) {
            .sidebar-shell { transform: translateX(0); }
            .sidebar-collapse-toggle { display: inline-flex; }
            html.sidebar-precollapsed .sidebar-shell { width: 4.75rem; }
            html.sidebar-precollapsed .app-main-shell { padding-left: 4.75rem; }
            html.sidebar-precollapsed .sidebar-header { justify-content: flex-start; gap: 0; padding-inline: .375rem; }
            html.sidebar-precollapsed .sidebar-collapse-toggle { width: 1.75rem; height: 1.75rem; margin-left: 0; padding: .375rem; }
            html.sidebar-precollapsed .sidebar-brand-copy,
            html.sidebar-precollapsed .sidebar-section-label,
            html.sidebar-precollapsed .sidebar-nav-label,
            html.sidebar-precollapsed .sidebar-nav-badge,
            html.sidebar-precollapsed .sidebar-profile-copy,
            html.sidebar-precollapsed .sidebar-profile-chevron { display: none !important; }
        }
    </style>
    <script>
        try {
            if (localStorage.getItem('sipakarbun.sidebar-collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-precollapsed');
            }
        } catch (error) {
            // Ignore storage restrictions; Alpine will use the expanded default.
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased">
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>
    <x-page-loader />

    <div class="min-h-full flex" x-data="{ sidebarOpen: false, sidebarCollapsed: localStorage.getItem('sipakarbun.sidebar-collapsed') === 'true' }" x-init="document.documentElement.classList.toggle('sidebar-precollapsed', sidebarCollapsed); $watch('sidebarCollapsed', value => { localStorage.setItem('sipakarbun.sidebar-collapsed', value); document.documentElement.classList.toggle('sidebar-precollapsed', value); })">
        @include('layouts.partials.sidebar')

        <div class="app-main-shell flex-1 flex flex-col min-w-0 transition-[padding] duration-300 ease-out"
             :class="sidebarCollapsed ? 'lg:pl-[4.75rem]' : 'lg:pl-64'">
            @include('layouts.partials.topbar')

            <main id="main-content" class="flex-1 overflow-y-auto bg-[#f7faf8] p-4 sm:p-6 lg:p-8">
                @if(session('success'))
                    <div class="mb-5 rounded-xl border border-[#bfe2cc] bg-[#effaf2] p-4 text-sm text-[#176b45] shadow-[0_4px_14px_rgba(23,107,69,.05)]" x-data="{ show: true }" x-show="show" x-transition role="status">
                        <div class="flex items-center justify-between">
                            <span class="pr-4">{{ session('success') }}</span>
                            <button @click="show = false" class="rounded-md px-1.5 text-[#5b9a73] transition-colors hover:bg-[#dff3e5] hover:text-[#176b45]" aria-label="Tutup notifikasi">&times;</button>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-5 rounded-xl border border-[#f0c5c0] bg-[#fff4f2] p-4 text-sm text-[#a83d32] shadow-[0_4px_14px_rgba(168,61,50,.05)]" x-data="{ show: true }" x-show="show" x-transition role="alert">
                        <div class="flex items-center justify-between">
                            <span class="pr-4">{{ session('error') }}</span>
                            <button @click="show = false" class="rounded-md px-1.5 text-[#c56a61] transition-colors hover:bg-[#fee4e0] hover:text-[#a83d32]" aria-label="Tutup notifikasi">&times;</button>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
