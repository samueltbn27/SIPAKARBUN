@php
    $user = auth()->user();
    $userName = $user?->name ?? 'Pengguna';
    $userRole = $user?->roles->pluck('name')->first() ?? 'user';
    $roleLabels = [
        'admin' => 'Admin Sistem',
        'operator_uptd' => 'Operator UPTD',
        'popt' => 'POPT',
        'poktan' => 'Poktan / Petani',
    ];
    $roleLabel = $roleLabels[$userRole] ?? ucfirst($userRole);
    $initials = strtoupper(implode('', array_map(
        fn ($w) => strtoupper(mb_substr($w, 0, 1)),
        array_slice(explode(' ', trim($userName)), 0, 2)
    ))) ?: '?';

    $routeName = (string) Route::currentRouteName();
    $crumbLabels = [
        'dashboard' => 'Dashboard',
        'diagnosis.index' => 'Diagnosis',
        'diagnosis.history' => 'Riwayat Diagnosis',
        'permohonan.index' => 'Permohonan Saya',
        'permohonan.create' => 'Ajukan Permohonan',
        'permohonan.show' => 'Detail Permohonan',
        'operator.permohonan' => 'Permohonan Masuk',
        'kasus.index' => 'Kasus',
        'popt.penugasan' => 'Penugasan Saya',
        'pengguna.index' => 'Pengguna',
    ];
    $crumbLabel = $crumbLabels[$routeName] ?? 'SIPAKARBUN';
@endphp

<header class="sticky top-0 z-30 flex h-16 flex-shrink-0 items-center gap-3 border-b border-[#e4ece7] bg-white/80 px-4 backdrop-blur sm:px-6">
    {{-- Hamburger (mobile) --}}
    <button type="button" @click="sidebarOpen = true"
            class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-[#66746c] hover:bg-[#f3f8f4] hover:text-[#176b45] lg:hidden"
            aria-label="Buka menu">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- Breadcrumb --}}
    <nav class="hidden min-w-0 flex-1 items-center gap-1.5 text-sm sm:flex" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" class="font-medium text-[#8a9990] transition-colors hover:text-[#176b45]">Dashboard</a>
        @if ($routeName !== 'dashboard')
            <svg class="h-3.5 w-3.5 flex-shrink-0 text-[#b9c4bd]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="truncate font-semibold text-[#173b29]">{{ $crumbLabel }}</span>
        @endif
    </nav>
    <div class="flex-1 sm:hidden"></div>

    {{-- Profil + logout --}}
    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
        <button type="button" @click="open = !open"
                class="flex items-center gap-2.5 rounded-full py-1.5 pl-1.5 pr-2 transition-colors hover:bg-[#f3f8f4]"
                aria-haspopup="true" :aria-expanded="open ? 'true' : 'false'">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#173b29] text-xs font-bold text-white">{{ $initials }}</span>
            <span class="hidden text-left md:block">
                <span class="block max-w-[160px] truncate text-sm font-semibold text-[#173b29]">{{ $userName }}</span>
                <span class="block text-xs text-[#8a9990]">{{ $roleLabel }}</span>
            </span>
            <svg class="h-4 w-4 text-[#9ba8a1]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="open" @click.outside="open = false" x-transition
             class="absolute right-0 mt-2 w-56 rounded-xl border border-[#e4ece7] bg-white p-1.5 shadow-lg" style="display: none;">
            <div class="border-b border-[#eef3ef] px-3 py-2.5">
                <div class="text-sm font-semibold text-[#173b29]">{{ $userName }}</div>
                <div class="text-xs text-[#8a9990]">{{ $user->email }}</div>
            </div>
            <a href="{{ route('dashboard') }}" @click="open = false"
               class="mt-1 flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-[#66746c] hover:bg-[#f3f8f4] hover:text-[#176b45]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                Dashboard
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    Keluar
                </button>
            </form>
        </div>
    </div>
</header>
