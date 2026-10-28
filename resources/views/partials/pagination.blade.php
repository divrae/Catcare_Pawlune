@if ($paginator->hasPages())
<nav class="row" aria-label="Halaman">
    @if ($paginator->onFirstPage())<span class="btn btn-sm disabled">‹</span>@else<a class="btn btn-sm" href="{{ $paginator->previousPageUrl() }}">‹</a>@endif
    <span class="badge">Hal {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
    @if ($paginator->hasMorePages())<a class="btn btn-sm" href="{{ $paginator->nextPageUrl() }}">›</a>@else<span class="btn btn-sm disabled">›</span>@endif
</nav>
@endif
