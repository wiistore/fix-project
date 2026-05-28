@php
    $emptyIcon = $emptyIcon ?? 'ti ti-database-off';
    $emptyTitle = $emptyTitle ?? 'Data belum tersedia';
    $emptyMessage = $emptyMessage ?? 'Belum ada data yang bisa ditampilkan saat ini.';
    $emptyActionUrl = $emptyActionUrl ?? null;
    $emptyActionLabel = $emptyActionLabel ?? null;
    $emptyActionIcon = $emptyActionIcon ?? 'ti ti-plus';
@endphp

<div class="app-empty-state">
    <div class="app-empty-icon">
        <i class="{{ $emptyIcon }}"></i>
    </div>

    <h3>{{ $emptyTitle }}</h3>
    <p>{{ $emptyMessage }}</p>

    @if (! empty($emptyActionUrl) && ! empty($emptyActionLabel))
        <a href="{{ url($emptyActionUrl) }}" class="btn btn-success app-empty-action">
            <i class="{{ $emptyActionIcon }}"></i>
            {{ $emptyActionLabel }}
        </a>
    @endif
</div>
