{{-- Pagination (LengthAwarePaginator). Utilisation : $items->links('partials.pagination') --}}
@if ($paginator->total() > 0)
    <div class="pagination">
        <div class="info">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} sur {{ number_format($paginator->total(), 0, ',', ' ') }}</div>
        @if ($paginator->hasPages())
            <ul>
                @if ($paginator->onFirstPage())
                    <li class="disabled"><span aria-hidden="true"><i class="ri-arrow-left-s-line"></i></span></li>
                @else
                    <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Page précédente"><i class="ri-arrow-left-s-line"></i></a></li>
                @endif
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="disabled"><span>{{ $element }}</span></li>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="active" aria-current="page"><span>{{ $page }}</span></li>
                            @else
                                <li><a href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach
                @if ($paginator->hasMorePages())
                    <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Page suivante"><i class="ri-arrow-right-s-line"></i></a></li>
                @else
                    <li class="disabled"><span aria-hidden="true"><i class="ri-arrow-right-s-line"></i></span></li>
                @endif
            </ul>
        @endif
    </div>
@endif
