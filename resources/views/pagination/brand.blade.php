@if ($paginator->hasPages())
    @php
        $base = 'inline-flex h-10 min-w-10 items-center justify-center rounded-lg px-3 text-sm font-semibold transition';
        $link = $base.' bg-indigo-50 text-indigo-700 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1';
        $active = $base.' bg-indigo-600 text-white shadow-sm';
        $disabled = $base.' cursor-not-allowed bg-slate-100 text-slate-400';
        $chevronLeft = 'M15 19l-7-7 7-7';
        $chevronRight = 'M9 5l7 7-7 7';
    @endphp

    <nav role="navigation" aria-label="{{ __('Pagination navigation') }}" class="flex justify-center">
        <ul class="inline-flex flex-wrap items-center justify-center gap-1.5">
            <li>
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('Previous page') }}" class="{{ $disabled }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chevronLeft }}"/></svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('Previous page') }}" class="{{ $link }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chevronLeft }}"/></svg>
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="{{ $base }} text-slate-400">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="{{ $active }}">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}" class="{{ $link }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('Next page') }}" class="{{ $link }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chevronRight }}"/></svg>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="{{ __('Next page') }}" class="{{ $disabled }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chevronRight }}"/></svg>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
