@props([
    'title' => '',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div class="mb-6">
    @if (count($breadcrumbs) > 0)
        <nav class="mb-2 flex items-center gap-1.5 text-xs text-[#8a9990]" aria-label="Breadcrumb">
            @foreach ($breadcrumbs as $crumb)
                @if (!empty($crumb['url']))
                    <a href="{{ $crumb['url'] }}" class="font-medium hover:text-[#176b45]">{{ $crumb['label'] }}</a>
                @else
                    <span class="font-semibold text-[#66746c]">{{ $crumb['label'] }}</span>
                @endif
                @if (! $loop->last)
                    <svg class="h-3 w-3 flex-shrink-0 text-[#b9c4bd]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                @endif
            @endforeach
        </nav>
    @endif

    <h1 class="text-2xl font-extrabold tracking-tight text-[#173b29]">{{ $title }}</h1>

    @if ($subtitle)
        <p class="mt-1 text-sm text-[#66746c]">{{ $subtitle }}</p>
    @endif
</div>
