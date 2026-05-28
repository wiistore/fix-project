@php
    $pageCss = ['assets/css/restock.css'];
    $pageScripts = ['assets/js/restock.js'];

    $restocks = $restocks ?? [];
    $summary = $summary ?? [];
    $flash = $flash ?? [];
    $tanggalMulai = $tanggalMulai ?? '';
    $tanggalSelesai = $tanggalSelesai ?? '';
    $filterTipe = $filterTipe ?? '';

    $success = $flash['success'] ?? null;
    $error = $flash['error'] ?? null;

    $totalRestock = (int) ($summary['total_restock'] ?? count($restocks));
    $totalQtyMasuk = (int) ($summary['total_qty_masuk'] ?? 0);
    $totalQtyKeluar = (int) ($summary['total_qty_keluar'] ?? 0);
    $totalNilai = (float) ($summary['total_nilai'] ?? 0);

    $summaryCards = [
        ['class' => 'summary-green', 'icon' => 'ti ti-stack-push', 'label' => 'Total Record', 'value' => $totalRestock, 'desc' => 'Semua penyesuaian stok'],
        ['class' => 'summary-blue', 'icon' => 'ti ti-package-import', 'label' => 'Stok Masuk', 'value' => $totalQtyMasuk, 'desc' => 'Total barang masuk'],
        ['class' => 'summary-red', 'icon' => 'ti ti-package-export', 'label' => 'Stok Keluar', 'value' => $totalQtyKeluar, 'desc' => 'Total barang keluar'],
        ['class' => 'summary-orange', 'icon' => 'ti ti-cash', 'label' => 'Nilai Total', 'value' => app_rupiah($totalNilai), 'desc' => 'Nilai semua penyesuaian'],
    ];
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Restock & Penyesuaian Stok',
    'activeMenu' => $activeMenu ?? 'restock',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="restock-page">
    @if ($success)
        <div class="restock-alert restock-alert-success">
            <i class="ti ti-circle-check"></i>
            <span>{{ $success }}</span>
        </div>
    @endif

    @if ($error)
        <div class="restock-alert restock-alert-error">
            <i class="ti ti-alert-triangle"></i>
            <span>{{ $error }}</span>
        </div>
    @endif

    <section class="restock-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="restock-hero-content">
            <span class="restock-eyebrow">
                <i class="ti ti-stack-push"></i>
                Penyesuaian Stok
            </span>
            <h2>Restock & Penyesuaian Stok</h2>
        </div>

        <div class="restock-hero-actions">
            <a href="{{ url('/admin/restock/create?tipe=masuk') }}" class="restock-btn restock-btn-primary">
                <i class="ti ti-plus"></i>
                Tambah Stok
            </a>
            <a href="{{ url('/admin/restock/create?tipe=keluar') }}" class="restock-btn restock-btn-danger">
                <i class="ti ti-minus"></i>
                Kurangi Stok
            </a>
        </div>
    </section>

    <section class="restock-summary summary-count-{{ count($summaryCards) }}" data-aos="fade-up" data-aos-delay="140">
        @foreach ($summaryCards as $idx => $card)
            <article class="restock-summary-card {{ $card['class'] }}" data-aos="zoom-in" data-aos-delay="{{ 80 + ($idx * 100) }}">
                <span class="restock-summary-icon"><i class="{{ $card['icon'] }}"></i></span>
                <div>
                    <small>{{ $card['label'] }}</small>
                    <strong>{{ $card['value'] }}</strong>
                    <p>{{ $card['desc'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="restock-panel" data-aos="fade-up" data-aos-delay="200">
        <div class="restock-panel-header">
            <div>
                <span>Inventori</span>
                <h3>Riwayat Penyesuaian Stok</h3>
            </div>

            <div class="restock-tools">
                <form action="{{ url('/admin/restock') }}" method="GET" class="restock-date-filter">
                    <label>
                        <span>Tanggal Mulai</span>
                        <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}">
                    </label>

                    <label>
                        <span>Tanggal Selesai</span>
                        <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai }}">
                    </label>

                    <label>
                        <span>Tipe</span>
                        <select name="tipe" class="restock-filter-select">
                            <option value="">Semua</option>
                            <option value="masuk" {{ $filterTipe === 'masuk' ? 'selected' : '' }}>Stok Masuk</option>
                            <option value="keluar" {{ $filterTipe === 'keluar' ? 'selected' : '' }}>Stok Keluar</option>
                        </select>
                    </label>

                    <button type="submit" class="restock-btn restock-btn-ghost">
                        <i class="ti ti-filter"></i> Filter
                    </button>

                    <a href="{{ url('/admin/restock') }}" class="restock-btn restock-btn-muted">
                        <i class="ti ti-refresh"></i> Reset
                    </a>
                </form>

                <label class="restock-search">
                    <i class="ti ti-search"></i>
                    <input type="search" placeholder="Cari barang, supplier, pembuat, catatan..." data-restock-search>
                </label>
            </div>
        </div>

        @if (empty($restocks))
            <div class="restock-empty">
                <span><i class="ti ti-stack-pop"></i></span>
                <h4>Belum ada data</h4>
                <p>Belum ada riwayat penyesuaian stok.</p>
                <a href="{{ url('/admin/restock/create?tipe=masuk') }}" class="restock-btn restock-btn-primary">
                    <i class="ti ti-plus"></i> Tambah Stok
                </a>
            </div>
        @else
            <div class="restock-table-wrap">
                <table class="restock-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tipe</th>
                            <th>Tanggal</th>
                            <th>Barang</th>
                            <th>Supplier</th>
                            <th>Qty</th>
                            <th>Harga Beli</th>
                            <th>Total Nilai</th>
                            <th>Dibuat Oleh</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>

                    <tbody data-restock-table-body>
                        @foreach ($restocks as $index => $r)
                            @php
                                $tipeRow = $r['tipe'] ?? 'masuk';
                                $isMasukRow = $tipeRow === 'masuk';
                                $alasanRow = $r['alasan'] ?? '';
                                $catatanRow = $r['catatan'] ?? '';
                                $keterangan = $isMasukRow ? $catatanRow : ($alasanRow !== '' ? $alasanRow : $catatanRow);

                                $searchText = strtolower(implode(' ', [
                                    $r['tanggal'] ?? '', $tipeRow,
                                    $r['kode_barang'] ?? '', $r['nama_barang'] ?? '',
                                    $r['nama_supplier'] ?? '', $r['dibuat_oleh'] ?? '',
                                    $catatanRow, $alasanRow,
                                ]));
                            @endphp

                            <tr data-restock-row data-search="{{ $searchText }}" data-tipe="{{ $tipeRow }}">
                                <td><span class="restock-number">{{ $index + 1 }}</span></td>

                                <td>
                                    <span class="restock-tipe-badge {{ $isMasukRow ? 'tipe-masuk' : 'tipe-keluar' }}">
                                        <i class="ti ti-{{ $isMasukRow ? 'arrow-up' : 'arrow-down' }}"></i>
                                        {{ $isMasukRow ? 'Masuk' : 'Keluar' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="restock-date">
                                        <i class="ti ti-calendar"></i>
                                        {{ app_date($r['tanggal'] ?? '', 'd M Y') }}
                                    </span>
                                </td>

                                <td>
                                    <div class="restock-product">
                                        <span class="restock-product-icon"><i class="ti ti-package"></i></span>
                                        <div>
                                            <strong>{{ $r['nama_barang'] ?? '-' }}</strong>
                                            <small>
                                                {{ $r['kode_barang'] ?? '-' }}
                                                @if (! empty($r['satuan']))
                                                    • {{ $r['satuan'] }}
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="restock-pill">
                                        <i class="ti ti-truck-delivery"></i>
                                        {{ $r['nama_supplier'] ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="restock-qty {{ $isMasukRow ? 'qty-masuk' : 'qty-keluar' }}">
                                        <i class="ti ti-{{ $isMasukRow ? 'plus' : 'minus' }}"></i>
                                        {{ (int) ($r['qty'] ?? 0) }}
                                    </span>
                                </td>

                                <td>
                                    <strong class="restock-money">{{ app_rupiah($r['harga_beli'] ?? 0) }}</strong>
                                </td>

                                <td>
                                    <strong class="restock-money">{{ app_rupiah($r['total_nilai'] ?? 0) }}</strong>
                                </td>

                                <td>
                                    <span class="restock-user">
                                        <i class="ti ti-user"></i>
                                        {{ $r['dibuat_oleh'] ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="restock-note">{{ app_short_text($keterangan, 60) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="restock-filter-empty" data-restock-filter-empty hidden>
                    <span><i class="ti ti-search-off"></i></span>
                    <h4>Data tidak ketemu</h4>
                    <p>Keyword-nya terlalu spesifik.</p>
                </div>
            </div>

            @include('components.pagination', ['pagination' => $pagination ?? null])
        @endif
    </section>
</div>
@endsection
