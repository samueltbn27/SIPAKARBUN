@php
    $currentRoute = Route::currentRouteName();
    $isActive = fn($match) => $match !== '' && $currentRoute && str_starts_with($currentRoute, $match);
    $userName = auth()->user()?->name ?? 'Admin KM';
    $userRole = auth()->user()?->roles->first()?->name ?? 'user';
    $roleLabels = ['admin' => 'Admin Sistem', 'operator_uptd' => 'Operator UPTD', 'popt' => 'POPT', 'pakar' => 'Knowledge Manager', 'pimpinan' => 'Pimpinan'];
    $roleLabel = $roleLabels[$userRole] ?? ucfirst($userRole);
    $initials = strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), explode(' ', trim($userName), 2))));
    $pendingUsers = $userRole === 'admin' ? \App\Models\User::where('is_active', false)->count() : 0;
    $navSections = [
        [
            'title' => 'Dashboard',
            'items' => [
                ['route' => 'knowledge.dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'match' => 'knowledge.dashboard'],
            ],
        ],
    ];

    if (auth()->user()?->hasAnyRole(['admin', 'operator_uptd', 'popt', 'pimpinan'])) {
        $navSections[0]['items'][] = [
            'route' => 'webgis.index',
            'label' => 'WebGIS',
            'icon' => 'M12 21s7-4.35 7-10a7 7 0 10-14 0c0 5.65 7 10 7 10Zm0-7a3 3 0 100-6 3 3 0 000 6Z',
            'match' => 'webgis',
        ];
    }

    // Master Data hanya untuk non-admin (pakar, operator, popt)
    if ($userRole !== 'admin') {
        $navSections[] = [
            'title' => 'Master Data',
            'items' => [
                ['route' => 'knowledge.komoditas.index', 'label' => 'Komoditas', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'match' => 'knowledge.komoditas'],
                ['route' => 'knowledge.penyakit.index', 'label' => 'Penyakit', 'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', 'match' => 'knowledge.penyakit'],
                ['route' => 'knowledge.gejala.index', 'label' => 'Gejala', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'match' => 'knowledge.gejala'],
                ['route' => 'knowledge.solusi.index', 'label' => 'Solusi / Rekomendasi', 'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z', 'match' => 'knowledge.solusi'],
                ['route' => 'knowledge.aturan-cf.index', 'label' => 'Aturan Penyakit-Gejala (CF)', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'match' => 'knowledge.aturan-cf'],
            ],
        ];
    }

    $navSections[] = [
        'title' => 'Knowledge',
        'items' => [
            ['route' => 'knowledge.publikasi.index', 'label' => 'Publikasi Knowledge', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', 'match' => 'knowledge.publikasi'],
            ['route' => 'knowledge.riwayat.index', 'label' => 'Riwayat Perubahan', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'match' => 'knowledge.riwayat'],
        ],
    ];

    // Section Pengaturan hanya untuk admin
    if ($userRole === 'admin') {
        $navSections[] = [
            'title' => 'Pengaturan',
            'items' => [
                ['route' => 'knowledge.pengguna.index', 'label' => 'Pengguna', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z', 'match' => 'knowledge.pengguna', 'badge' => $pendingUsers],
                ['route' => '#', 'label' => 'Pengaturan', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'match' => ''],
            ],
        ];
    }
@endphp

<div class="fixed inset-0 z-40 bg-[#173b29]/40 lg:hidden" x-show="sidebarOpen" @click="sidebarOpen = false" style="display: none;"></div>

<!-- SIDEBAR v2.1 -->
<aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-[#e4ece7] flex flex-col transform transition-transform duration-200 -translate-x-full lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : ''">
    {{-- Header --}}
    <div class="flex items-center gap-3 h-20 px-5 border-b border-[#eef3ef] flex-shrink-0">
        <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-[#176b45] text-white flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 4C10 4 5 8 5 15c0 2.2 1.8 4 4 4 7 0 11-6 11-15ZM4 20c3-4 6-6 10-8"/></svg>
        </span>
        <div class="min-w-0">
            <div class="text-base font-extrabold tracking-tight text-[#173b29] leading-tight">SIPAKARBUN</div>
            <div class="text-[10px] font-medium tracking-wide text-[#8a9990] uppercase">Knowledge Management</div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="sidebar-scroll flex-1 overflow-y-auto px-3 py-4">
        @foreach($navSections as $section)
            <div class="px-3 pb-2 pt-{{ $loop->first ? '0' : '5' }} text-[10px] font-bold text-[#a0aba4] uppercase tracking-[.14em]">{{ $section['title'] }}</div>
            <div class="space-y-0.5 pb-2">
                @foreach($section['items'] as $item)
                    <?php $active = $isActive($item['match']); ?>
                    <a href="{{ $item['route'] === '#' ? '#' : route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ $active ? 'bg-[#e8f4ed] text-[#176b45] font-semibold' : 'text-[#66746c] hover:bg-[#f3f8f4] hover:text-[#176b45]' }}
                              {{ $item['route'] === '#' ? 'opacity-50 pointer-events-none' : '' }}">
                        <svg class="w-[18px] h-[18px] flex-shrink-0 {{ $active ? 'text-[#176b45]' : 'text-[#9ba8a1]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['icon'] }}"/>
                        </svg>
                        <span class="truncate flex-1">{{ $item['label'] }}</span>
                        @if(!empty($item['badge']) && $item['badge'] > 0)
                            <span class="flex-shrink-0 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-[#d7a735] text-white text-[10px] font-bold">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    {{-- User Profile + Logout --}}
    <div class="flex-shrink-0 border-t border-[#eef3ef] p-3" x-data="{ profileOpen: false }">
        <div class="relative">
            <button @click="profileOpen = !profileOpen" class="flex items-center gap-3 w-full px-2 py-2 rounded-lg hover:bg-[#f3f8f4] transition-colors">
                <span class="flex items-center justify-center w-9 h-9 rounded-full bg-[#dcefe3] text-xs font-bold text-[#176b45] flex-shrink-0">{{ $initials }}</span>
                <div class="min-w-0 text-left flex-1">
                    <div class="text-sm font-semibold text-[#173b29] truncate">{{ $userName }}</div>
                    <div class="text-[11px] text-[#8b9790] truncate">{{ $roleLabel }}</div>
                </div>
                <svg class="w-4 h-4 text-[#8b9790] flex-shrink-0 transition-transform" :class="profileOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6"/></svg>
            </button>

            {{-- Dropdown --}}
            <div x-show="profileOpen" x-cloak x-transition style="display: none;"
                 @click.outside="profileOpen = false"
                 class="absolute bottom-full left-0 right-0 mb-2 bg-white rounded-xl border border-[#e4ece7] shadow-lg py-1.5">
                <div class="px-3 py-2 border-b border-[#f0f4f1]">
                    <div class="text-sm font-semibold text-[#173b29]">{{ $userName }}</div>
                    <div class="text-[11px] text-[#8b9790]">{{ auth()->user()?->email ?? '' }}</div>
                </div>
                <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-sm text-[#66746c] hover:bg-[#f3f8f4] hover:text-[#176b45] transition-colors opacity-50 pointer-events-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profil Saya
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-2.5 w-full px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
