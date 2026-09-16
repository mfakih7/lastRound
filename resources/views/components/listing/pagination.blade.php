@props(['paginator'])

@if ($paginator->hasPages() || $paginator->total() > 0)
    <div class="flex flex-col gap-3 border-t border-line px-4 py-3 text-sm text-muted sm:flex-row sm:items-center sm:justify-between sm:px-5">
        <p>
            Showing
            <span class="font-semibold text-ink">{{ $paginator->firstItem() ?? 0 }}</span>
            to
            <span class="font-semibold text-ink">{{ $paginator->lastItem() ?? 0 }}</span>
            of
            <span class="font-semibold text-ink">{{ $paginator->total() }}</span>
        </p>
        <div class="w-full sm:w-auto">
            {{ $paginator->onEachSide(1)->links() }}
        </div>
    </div>
@endif
