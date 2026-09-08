@props([
    'title' => '',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div class="mb-5 sm:mb-6">
    @if (count($breadcrumbs) > 0)
        <nav class="mb-2 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-[#8a9990]" aria-label="Breadcrumb">
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

    <h1 class="break-words text-xl font-extrabold tracking-tight text-[#173b29] sm:text-2xl">{{ $title }}</h1>

    @if ($subtitle)
        <p class="mt-1 text-sm text-[#66746c]">{{ $subtitle }}</p>
    @endif
</div>
