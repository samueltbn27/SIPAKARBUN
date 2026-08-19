@php
    $currentRoute = Route::currentRouteName();
    $isActive = fn ($match) => $match !== '' && $currentRoute !== null && str_starts_with($currentRoute, $match);

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

    $navSections = [];
    $dashboardItem = [
        'route' => 'dashboard',
        'label' => 'Dashboard',
        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'match' => 'dashboard',
    ];

    if ($userRole === 'poktan') {
        $navSections[] = [
            'title' => 'Menu',
            'items' => [
                $dashboardItem,
                ['route' => 'diagnosis.index', 'label' => 'Diagnosis', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'match' => 'diagnosis.index'],
                ['route' => 'diagnosis.history', 'label' => 'Riwayat Diagnosis', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'match' => 'diagnosis.history'],
            ],
        ];
        $navSections[] = [
            'title' => 'Permohonan',
            'items' => [
                ['route' => 'permohonan.index', 'label' => 'Permohonan Saya', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'match' => 'permohonan.index'],
            ],
        ];
    } elseif ($userRole === 'operator_uptd') {
        $navSections[] = [
            'title' => 'Menu',
            'items' => [
                $dashboardItem,
                ['route' => 'operator.permohonan', 'label' => 'Permohonan Masuk', 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4', 'match' => 'operator.permohonan'],
            ],
        ];
        $navSections[] = [
            'title' => 'Penanganan',
            'items' => [
                ['route' => 'kasus.index', 'label' => 'Kasus', 'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', 'match' => 'kasus.index'],
            ],
        ];
    } elseif ($userRole === 'popt') {
        $navSections[] = [
            'title' => 'Menu',
            'items' => [
                $dashboardItem,
                ['route' => 'popt.penugasan', 'label' => 'Penugasan Saya', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'match' => 'popt.penugasan'],
            ],
        ];
    } elseif ($userRole === 'admin') {
        $navSections[] = [
            'title' => 'Menu',
            'items' => [
                $dashboardItem,
                ['route' => 'operator.permohonan', 'label' => 'Permohonan Masuk', 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4', 'match' => 'operator.permohonan'],
                ['route' => 'kasus.index', 'label' => 'Kasus', 'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', 'match' => 'kasus.index'],
            ],
        ];
        $navSections[] = [
            'title' => 'Administrasi',
            'items' => [
                ['route' => 'pengguna.index', 'label' => 'Pengguna', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z', 'match' => 'pengguna.index'],
            ],
        ];
    } else {
        $navSections[] = ['title' => 'Menu', 'items' => [$dashboardItem]];
    }
@endphp

<div class="fixed inset-0 z-40 bg-[#173b29]/40 lg:hidden" x-show="sidebarOpen" @click="sidebarOpen = false" style="display: none;"></div>

{{-- SIDEBAR — responsive, per-role, mengikuti desain M1 --}}
<aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-[#e4ece7] flex flex-col transform transition-transform duration-200 -translate-x-full lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : ''">

    {{-- Header brand --}}
    <div class="flex items-center gap-3 h-20 px-5 border-b border-[#eef3ef] flex-shrink-0">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#173b29] text-white flex-shrink-0">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21V9M12 9c-3 0-5.5-2.5-5.5-5.5 3 0 5.5 2.5 5.5 5.5zM12 9c3 0 5.5-2.5 5.5-5.5C14.5 3.5 12 6 12 9zM8 15h8M9 18h6" />
            </svg>
        </div>
        <div class="min-w-0">
            <div class="text-base font-extrabold tracking-tight text-[#173b29] leading-tight">SIPAKARBUN</div>
            <div class="text-[10px] font-medium tracking-wide text-[#8a9990] uppercase">Case Management</div>
        </div>
    </div>

    {{-- Navigasi per role --}}
    <nav class="sidebar-scroll flex-1 overflow-y-auto px-3 py-4">
        @foreach ($navSections as $section)
            <div class="px-3 pb-2 pt-{{ $loop->first ? '0' : '5' }} text-[10px] font-bold text-[#a0aba4] uppercase tracking-[.14em]">{{ $section['title'] }}</div>
            <div class="space-y-0.5 pb-2">
                @foreach ($section['items'] as $item)
                    @php $active = $isActive($item['match']); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ $active ? 'bg-[#e8f4ed] text-[#176b45] font-semibold' : 'text-[#66746c] hover:bg-[#f3f8f4] hover:text-[#176b45]' }}">
                        <svg class="w-[18px] h-[18px] flex-shrink-0 {{ $active ? 'text-[#176b45]' : 'text-[#9ba8a1]' }}" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                        </svg>
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    {{-- Profil ringkas pengguna --}}
    <div class="border-t border-[#eef3ef] p-4 flex-shrink-0">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e8f4ed] text-xs font-bold text-[#176b45]">{{ $initials }}</span>
            <div class="min-w-0">
                <div class="text-sm font-semibold text-[#173b29] truncate">{{ $userName }}</div>
                <div class="text-xs text-[#8a9990]">{{ $roleLabel }}</div>
            </div>
        </div>
    </div>
</aside>
