@php
    $topbarTitle = html_entity_decode(trim($__env->yieldContent('topbar-title')) ?: trim($__env->yieldContent('title', 'Dashboard')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $topbarSubtitle = html_entity_decode(trim($__env->yieldContent('topbar-subtitle')) ?: trim($__env->yieldContent('subtitle', 'Ringkasan data knowledge management SIPAKARBUN')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $knowledgeFormWidth = trim($__env->yieldContent('knowledge-form-width'));
    $hasTopbarActions = $__env->hasSection('topbar-actions');
@endphp

<header class="sticky top-0 z-30 border-b border-[#e4ece7] bg-white/90 backdrop-blur-md">
    <div class="{{ $knowledgeFormWidth ? 'px-4 sm:px-6 lg:px-8' : 'px-4 sm:px-8' }}">
        <div class="flex {{ $hasTopbarActions ? 'min-h-20 flex-col items-stretch gap-3 py-4 sm:flex-row sm:items-center sm:justify-between' : 'items-center justify-between '.($knowledgeFormWidth ? 'min-h-20 py-3' : 'h-20') }} {{ $knowledgeFormWidth === 'complex' ? 'knowledge-form-width--complex' : ($knowledgeFormWidth === 'standard' ? 'knowledge-form-width--standard' : '') }}">
            <div class="flex min-w-0 items-center gap-3 {{ $hasTopbarActions ? 'sm:flex-1' : '' }}">
                <button type="button" @click="sidebarOpen = !sidebarOpen" class="shrink-0 rounded-lg p-2 text-[#708078] transition-colors hover:bg-action-secondary-hover hover:text-[#176b45] lg:hidden" :aria-label="sidebarOpen ? 'Tutup navigasi' : 'Buka navigasi'" :aria-expanded="sidebarOpen.toString()" aria-controls="app-sidebar">
                    <svg width="24" height="24" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="min-w-0">
                    <h1 class="text-lg font-bold text-[#173b29] truncate">{{ $topbarTitle }}</h1>
                    <p class="{{ $knowledgeFormWidth || $hasTopbarActions ? 'line-clamp-2' : 'hidden sm:block' }} mt-0.5 text-xs text-[#89968e]">{{ $topbarSubtitle }}</p>
                </div>
            </div>
            @if ($hasTopbarActions)
                <div class="w-full shrink-0 sm:w-auto">
                    @yield('topbar-actions')
                </div>
            @endif
        </div>
    </div>
</header>
