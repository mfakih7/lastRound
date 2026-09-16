@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-end gap-1.5">
        @if ($paginator->onFirstPage())
            <span class="pager-link pager-link-disabled">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pager-link">Previous</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pager-link">Next</a>
        @else
            <span class="pager-link pager-link-disabled">Next</span>
        @endif
    </nav>
@endif
