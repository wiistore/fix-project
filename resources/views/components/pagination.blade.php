@php
    $pagination = $pagination ?? null;

    if (! is_array($pagination)) {
        return;
    }

    $currentPage = max(1, (int) ($pagination['current_page'] ?? $pagination['page'] ?? 1));
    $perPage = max(1, (int) ($pagination['per_page'] ?? 10));
    $total = max(0, (int) ($pagination['total'] ?? 0));
    $totalPages = max(1, (int) ($pagination['total_pages'] ?? ceil($total / $perPage)));

    if ($total <= 0) {
        return;
    }

    $from = (($currentPage - 1) * $perPage) + 1;
    $to = min($total, $currentPage * $perPage);

    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($currentPage <= 3) {
        $end = min($totalPages, 5);
    }
    if ($currentPage >= $totalPages - 2) {
        $start = max(1, $totalPages - 4);
    }

    $pageUrl = function (int $page) {
        $parts = parse_url(request()->getRequestUri());
        $path = $parts['path'] ?? '';
        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $query['page'] = max(1, $page);
        return $path.'?'.http_build_query($query);
    };
@endphp

<div class="app-pagination">
    <div class="app-pagination-info">
        Menampilkan
        <strong>{{ $from }}</strong>
        -
        <strong>{{ $to }}</strong>
        dari
        <strong>{{ $total }}</strong>
        data
    </div>

    <nav class="app-pagination-nav" aria-label="Pagination">
        <a
            href="{{ $pageUrl(max(1, $currentPage - 1)) }}"
            class="app-pagination-btn {{ $currentPage <= 1 ? 'is-disabled' : '' }}"
            aria-label="Halaman sebelumnya"
        >
            <i class="ti ti-chevron-left"></i>
        </a>

        @if ($start > 1)
            <a href="{{ $pageUrl(1) }}" class="app-pagination-btn">1</a>
            @if ($start > 2)
                <span class="app-pagination-dots">...</span>
            @endif
        @endif

        @for ($p = $start; $p <= $end; $p++)
            <a
                href="{{ $pageUrl($p) }}"
                class="app-pagination-btn {{ $p === $currentPage ? 'is-active' : '' }}"
            >
                {{ $p }}
            </a>
        @endfor

        @if ($end < $totalPages)
            @if ($end < $totalPages - 1)
                <span class="app-pagination-dots">...</span>
            @endif
            <a href="{{ $pageUrl($totalPages) }}" class="app-pagination-btn">{{ $totalPages }}</a>
        @endif

        <a
            href="{{ $pageUrl(min($totalPages, $currentPage + 1)) }}"
            class="app-pagination-btn {{ $currentPage >= $totalPages ? 'is-disabled' : '' }}"
            aria-label="Halaman berikutnya"
        >
            <i class="ti ti-chevron-right"></i>
        </a>
    </nav>
</div>
