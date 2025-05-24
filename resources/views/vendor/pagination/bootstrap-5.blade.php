@if ($paginator->hasPages())
    <nav class="d-flex justify-items-center justify-content-between">
        <div class="d-flex justify-content-between flex-fill d-sm-none">
            <ul class="pagination">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">@lang('pagination.previous')</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">@lang('pagination.previous')</a>
                    </li>
                @endif

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">@lang('pagination.next')</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">@lang('pagination.next')</span>
                    </li>
                @endif
            </ul>
        </div>

        <div class="d-none flex-sm-fill d-sm-flex align-items-sm-center justify-content-sm-between">
            <div>
                <p class="small text-muted">
                    Mostrando del {{ $paginator->firstItem() }}
                    al {{ $paginator->lastItem() }}
                    de {{ $paginator->total() }} resultados
                </p>
            </div>

            <div>
                <ul class="pagination">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                            <span class="page-link" aria-hidden="true">&lsaquo;</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                        </li>
                    @endif

                   {{-- Botón Primera Página --}}
                    @if (!$paginator->onFirstPage())
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->url(1) }}" aria-label="Primera página">&laquo;</a>
                        </li>
                    @endif

                    @php
                        $current = $paginator->currentPage();
                        $last = $paginator->lastPage();
                        $start = max(1, $current - 2);
                        $end = min($last, $current + 2);

                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . $paginator->url(1) . '">1</a></li>';
                            if ($start > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                            }
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            if ($i == $current) {
                                echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                            } else {
                                echo '<li class="page-item"><a class="page-link" href="' . $paginator->url($i) . '">' . $i . '</a></li>';
                            }
                        }

                        if ($end < $last) {
                            if ($end < $last - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . $paginator->url($last) . '">' . $last . '</a></li>';
                        }
                    @endphp

                    {{-- Botón Última Página --}}
                    @if ($paginator->currentPage() < $paginator->lastPage())
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}" aria-label="Última página">&raquo;</a>
                        </li>
                    @endif

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                            <span class="page-link" aria-hidden="true">&rsaquo;</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
@endif
