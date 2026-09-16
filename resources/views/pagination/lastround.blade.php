@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-end gap-1.5">
        @if ($paginator->onFirstPage())
            <span class="pager-link pager-link-disabled">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pager-link">Previous</a>
        @endif

        <div class="hidden items-center gap-1.5 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-1 text-sm text-zinc-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager-link pager-link-active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pager-link">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        <span class="pager-link pager-link-active sm:hidden">{{ $paginator->currentPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pager-link">Next</a>
        @else
            <span class="pager-link pager-link-disabled">Next</span>
        @endif
    </nav>
@endif
