<header class="sticky top-0 z-30 border-b border-[#e4ece7] bg-white/90 backdrop-blur-md">
    <div class="flex h-20 items-center justify-between px-4 sm:px-8">
        <div class="flex items-center gap-3 min-w-0">
            <button @click="sidebarOpen = true" class="rounded-lg p-2 text-[#708078] transition-colors hover:bg-[#eff7f1] hover:text-[#176b45] lg:hidden" aria-label="Buka navigasi">
                <svg width="24" height="24" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-[#173b29] truncate">@yield('title', 'Dashboard')</h1>
                <p class="hidden sm:block text-xs text-[#89968e] mt-0.5">@yield('subtitle', 'Ringkasan data knowledge management SIPAKARBUN')</p>
            </div>
        </div>

    </div>
</header>
