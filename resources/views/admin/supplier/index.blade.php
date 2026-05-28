@php
    $pageCss = ['assets/css/supplier.css'];
    $pageScripts = ['assets/js/supplier.js'];

    $suppliers = $suppliers ?? [];
    $flash = $flash ?? [];
    $success = $flash['success'] ?? null;
    $error = $flash['error'] ?? null;

    $totalSupplier = count($suppliers);
    $totalAktif = 0;
    $totalNonaktif = 0;
    $totalRestock = 0;

    foreach ($suppliers as $s) {
        $st = strtolower((string) ($s['status'] ?? ''));
        if ($st === 'aktif') $totalAktif++;
        if ($st === 'nonaktif') $totalNonaktif++;
        $totalRestock += (int) ($s['total_restock'] ?? 0);
    }

    $summaryCards = [
        ['class' => 'summary-green', 'icon' => 'ti ti-truck-delivery', 'label' => 'Total Supplier', 'value' => $totalSupplier, 'desc' => 'Semua supplier'],
        ['class' => 'summary-blue', 'icon' => 'ti ti-circle-check', 'label' => 'Supplier Aktif', 'value' => $totalAktif, 'desc' => 'Bisa dipakai restock'],
        ['class' => 'summary-red', 'icon' => 'ti ti-circle-off', 'label' => 'Nonaktif', 'value' => $totalNonaktif, 'desc' => 'Tidak dipakai'],
        ['class' => 'summary-orange', 'icon' => 'ti ti-stack-push', 'label' => 'Total Restock', 'value' => $totalRestock, 'desc' => 'Riwayat dari supplier'],
    ];
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Data Supplier',
    'activeMenu' => $activeMenu ?? 'supplier',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="supplier-page">
    @if ($success)
        <div class="supplier-alert supplier-alert-success">
            <i class="ti ti-circle-check"></i>
            <span>{{ $success }}</span>
        </div>
    @endif

    @if ($error)
        <div class="supplier-alert supplier-alert-error">
            <i class="ti ti-alert-triangle"></i>
            <span>{{ $error }}</span>
        </div>
    @endif

    <section class="supplier-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="supplier-hero-content">
            <span class="supplier-eyebrow">
                <i class="ti ti-truck-delivery"></i>
                Master Supplier
            </span>
            <h2>Data Supplier</h2>
        </div>

        <div class="supplier-hero-actions">
            <a href="{{ url('/admin/supplier/create') }}" class="supplier-btn supplier-btn-primary">
                <i class="ti ti-plus"></i>
                Tambah Supplier
            </a>
            <a href="{{ url('/admin/restock') }}" class="supplier-btn supplier-btn-soft">
                <i class="ti ti-stack-push"></i>
                Lihat Restock
            </a>
        </div>
    </section>

    <section class="supplier-summary summary-count-{{ count($summaryCards) }}" data-aos="fade-up" data-aos-delay="140">
        @foreach ($summaryCards as $idx => $card)
            <article class="supplier-summary-card {{ $card['class'] }}" data-aos="zoom-in" data-aos-delay="{{ 80 + ($idx * 100) }}">
                <span class="supplier-summary-icon"><i class="{{ $card['icon'] }}"></i></span>
                <div>
                    <small>{{ $card['label'] }}</small>
                    <strong>{{ $card['value'] }}</strong>
                    <p>{{ $card['desc'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="supplier-panel" data-aos="fade-up" data-aos-delay="200">
        <div class="supplier-panel-header">
            <div>
                <span>Inventori</span>
                <h3>Daftar Supplier</h3>
            </div>

            <div class="supplier-tools">
                <label class="supplier-search">
                    <i class="ti ti-search"></i>
                    <input type="search" placeholder="Cari supplier, kontak, no HP, alamat..." data-supplier-search>
                </label>

                <select class="supplier-filter" data-supplier-status-filter aria-label="Filter status">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>

                <button type="button" class="supplier-btn supplier-btn-ghost" data-supplier-reset>
                    <i class="ti ti-refresh"></i>
                    Reset
                </button>
            </div>
        </div>

        @if (empty($suppliers))
            <div class="supplier-empty">
                <span><i class="ti ti-truck-off"></i></span>
                <h4>Belum ada supplier</h4>
                <p>Tambahkan supplier dulu supaya restock punya sumber barang yang jelas.</p>
                <a href="{{ url('/admin/supplier/create') }}" class="supplier-btn supplier-btn-primary">
                    <i class="ti ti-plus"></i> Tambah Supplier
                </a>
            </div>
        @else
            <div class="supplier-table-wrap">
                <table class="supplier-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Supplier</th>
                            <th>Kontak Person</th>
                            <th>No HP</th>
                            <th>Alamat</th>
                            <th>Restock</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody data-supplier-table-body>
                        @foreach ($suppliers as $index => $s)
                            @php
                                $st = strtolower((string) ($s['status'] ?? 'nonaktif'));
                                $totalSR = (int) ($s['total_restock'] ?? 0);
                                $searchText = strtolower(implode(' ', [
                                    $s['nama'] ?? '', $s['kontak_person'] ?? '',
                                    $s['no_hp'] ?? '', $s['alamat'] ?? '',
                                    $s['keterangan'] ?? '', $st,
                                ]));
                            @endphp
                            <tr data-supplier-row data-search="{{ $searchText }}" data-status="{{ $st }}">
                                <td>
                                    <span class="supplier-number">{{ $index + 1 }}</span>
                                </td>

                                <td>
                                    <div class="supplier-name">
                                        <span class="supplier-name-icon"><i class="ti ti-truck-delivery"></i></span>
                                        <div>
                                            <strong>{{ $s['nama'] ?? '-' }}</strong>
                                            @if (! empty($s['keterangan']))
                                                <small>{{ app_short_text($s['keterangan'], 55) }}</small>
                                            @else
                                                <small>Tidak ada catatan</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="supplier-pill">
                                        <i class="ti ti-user"></i>
                                        {{ ($s['kontak_person'] ?? '') !== '' ? $s['kontak_person'] : '-' }}
                                    </span>
                                </td>

                                <td>
                                    @if (! empty($s['no_hp']))
                                        <a href="tel:{{ $s['no_hp'] }}" class="supplier-phone">
                                            <i class="ti ti-phone"></i>
                                            {{ $s['no_hp'] }}
                                        </a>
                                    @else
                                        <span class="supplier-muted">-</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="supplier-address">{{ app_short_text($s['alamat'] ?? '', 70) }}</span>
                                </td>

                                <td>
                                    <span class="supplier-restock {{ $totalSR > 0 ? 'has-restock' : 'no-restock' }}">
                                        <i class="ti ti-stack-push"></i>
                                        {{ $totalSR }}
                                    </span>
                                </td>

                                <td>
                                    <span class="supplier-status {{ $st === 'aktif' ? 'status-active' : 'status-inactive' }}">
                                        <i class="{{ $st === 'aktif' ? 'ti ti-circle-check' : 'ti ti-circle-off' }}"></i>
                                        {{ ucfirst($st) }}
                                    </span>
                                </td>

                                <td>
                                    <div class="supplier-actions">
                                        <a href="{{ url('/admin/supplier/edit/'.($s['id'] ?? '')) }}"
                                           class="supplier-action-btn action-edit"
                                           title="Edit supplier">
                                            <i class="ti ti-edit"></i>
                                        </a>

                                        <form action="{{ url('/admin/supplier/delete/'.($s['id'] ?? '')) }}"
                                              method="POST"
                                              data-supplier-delete-form
                                              data-confirm-title="{{ $totalSR > 0 ? 'Nonaktifkan Supplier' : 'Hapus Supplier' }}"
                                              data-confirm-message="Supplier {{ $s['nama'] ?? '-' }} {{ $totalSR > 0 ? 'dinonaktifkan karena ada histori.' : 'akan dihapus karena belum dipakai.' }}">
                                            @csrf
                                            <button type="submit"
                                                    class="supplier-action-btn action-delete"
                                                    title="{{ $totalSR > 0 ? 'Nonaktifkan' : 'Hapus' }}">
                                                <i class="{{ $totalSR > 0 ? 'ti ti-user-off' : 'ti ti-trash' }}"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="supplier-filter-empty" data-supplier-filter-empty hidden>
                    <span><i class="ti ti-search-off"></i></span>
                    <h4>Data tidak ketemu</h4>
                    <p>Keyword atau filter terlalu sakti. Longgarkan dikit.</p>
                </div>
            </div>

            @include('components.pagination', ['pagination' => $pagination ?? null])
        @endif
    </section>
</div>
@endsection
