<header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-[#e8efea]">
    <div class="flex items-center justify-between h-20 px-4 sm:px-8">
        <div class="flex items-center gap-3 min-w-0">
            <button @click="sidebarOpen = true" class="lg:hidden p-2 text-[#708078] hover:text-[#176b45]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-[#173b29] truncate">@yield('title', 'Dashboard')</h1>
                <p class="hidden sm:block text-xs text-[#89968e] mt-0.5">@yield('subtitle', 'Ringkasan data knowledge management SIPAKARBUN')</p>
            </div>
        </div>
    </div>
</header>
