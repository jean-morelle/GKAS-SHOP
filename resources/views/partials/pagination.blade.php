@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm">
        <p class="hidden text-slate-500 sm:block">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} sur {{ $paginator->total() }}
        </p>
        <div class="flex flex-1 items-center justify-between gap-1 sm:flex-none sm:justify-end">
            @if ($paginator->onFirstPage())
                <span class="btn btn-secondary btn-sm opacity-50">Précédent</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary btn-sm">Précédent</a>
            @endif

            <span class="hidden items-center gap-1 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-2 text-slate-400">…</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="rounded-md bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="rounded-md px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary btn-sm">Suivant</a>
            @else
                <span class="btn btn-secondary btn-sm opacity-50">Suivant</span>
            @endif
        </div>
    </nav>
@endif
