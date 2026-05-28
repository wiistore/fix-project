@php
    $pageCss = ['assets/css/laporan.css'];
    $pageScripts = ['assets/js/laporan.js'];
    $useChart = true;

    $summary = $summary ?? [];
    $restockByBarang = $restockByBarang ?? [];
    $restockBySupplier = $restockBySupplier ?? [];
    $tanggalMulai = $tanggalMulai ?? '';
    $tanggalSelesai = $tanggalSelesai ?? '';

    $exportUrl = function (string $path) use ($tanggalMulai, $tanggalSelesai) {
        $q = [];
        if ($tanggalMulai !== '') $q['tanggal_mulai'] = $tanggalMulai;
        if ($tanggalSelesai !== '') $q['tanggal_selesai'] = $tanggalSelesai;
        return url($path).(empty($q) ? '' : '?'.http_build_query($q));
    };

    $totalRestock = (int) ($summary['total_restock'] ?? 0);
    $totalQty = (int) ($summary['total_qty'] ?? 0);
    $totalNilai = (float) ($summary['total_nilai'] ?? 0);

    $topBarang = $restockByBarang[0] ?? null;
    $topSupplier = $restockBySupplier[0] ?? null;
    $rataNilaiRestock = $totalRestock > 0 ? $totalNilai / $totalRestock : 0;

    $summaryCards = [
        ['class' => 'summary-green', 'icon' => 'ti ti-stack-push', 'label' => 'Total Restock', 'value' => $totalRestock, 'desc' => 'Transaksi stok masuk'],
        ['class' => 'summary-blue', 'icon' => 'ti ti-packages', 'label' => 'Qty Masuk', 'value' => number_format($totalQty, 0, ',', '.'), 'desc' => 'Total barang masuk'],
        ['class' => 'summary-orange', 'icon' => 'ti ti-cash', 'label' => 'Nilai Restock', 'value' => app_rupiah($totalNilai), 'desc' => 'Total modal restock'],
        ['class' => 'summary-purple', 'icon' => 'ti ti-calculator', 'label' => 'Rata Restock', 'value' => app_rupiah($rataNilaiRestock), 'desc' => 'Nilai rata-rata transaksi'],
    ];

    $chartRestockBarang = ['labels' => [], 'values' => [], 'datasetLabel' => 'Qty Masuk', 'tooltipMode' => 'number'];
    foreach (array_slice($restockByBarang, 0, 10) as $row) {
        $chartRestockBarang['labels'][] = (string) ($row['nama_barang'] ?? '-');
        $chartRestockBarang['values'][] = (int) ($row['total_qty'] ?? 0);
    }

    $chartRestockSupplier = ['labels' => [], 'values' => [], 'datasetLabel' => 'Nilai Restock', 'tooltipMode' => 'money'];
    foreach (array_slice($restockBySupplier, 0, 8) as $row) {
        $chartRestockSupplier['labels'][] = (string) ($row['nama_supplier'] ?? '-');
        $chartRestockSupplier['values'][] = (float) ($row['total_nilai'] ?? 0);
    }
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Laporan Restock',
    'activeMenu' => $activeMenu ?? 'laporan',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
    'useChart' => $useChart,
])

@section('content')
<div class="laporan-page">
    <section class="laporan-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="laporan-hero-content">
            <span class="laporan-eyebrow">
                <i class="ti ti-stack-push"></i>
                Laporan Restock
            </span>
            <h2>Restock</h2>
            <p>Pantau barang masuk, total modal restock, supplier paling aktif, dan produk yang paling sering diisi ulang.</p>
        </div>

        <div class="laporan-hero-actions">
            <a href="{{ $exportUrl('/admin/laporan/export/restock') }}" class="laporan-btn laporan-btn-primary">
                <i class="ti ti-file-spreadsheet"></i>
                Export Excel
            </a>
            <a href="{{ url('/admin/restock') }}" class="laporan-btn laporan-btn-soft">
                <i class="ti ti-stack-push"></i>
                Data Restock
            </a>
        </div>
    </section>

    <section class="laporan-nav" data-aos="fade-up" data-aos-delay="100">
        <a href="{{ url('/admin/laporan') }}"><i class="ti ti-layout-dashboard"></i> Ringkasan</a>
        <a href="{{ url('/admin/laporan/penjualan') }}"><i class="ti ti-cash"></i> Penjualan</a>
        <a href="{{ url('/admin/laporan/laba') }}"><i class="ti ti-chart-line"></i> Laba</a>
        <a href="{{ url('/admin/laporan/barang-terlaris') }}"><i class="ti ti-award"></i> Barang Terlaris</a>
        <a href="{{ url('/admin/laporan/restock') }}" class="is-active"><i class="ti ti-stack-push"></i> Restock</a>
    </section>

    <section class="laporan-filter-panel" data-aos="fade-up" data-aos-delay="150">
        <div>
            <span>Filter Periode</span>
            <h3>Atur Rentang Tanggal</h3>
        </div>

        <form action="{{ url('/admin/laporan/restock') }}" method="GET" class="laporan-filter-form">
            <label>
                <span>Tanggal Mulai</span>
                <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}">
            </label>
            <label>
                <span>Tanggal Selesai</span>
                <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai }}">
            </label>
            <button type="submit" class="laporan-btn laporan-btn-ghost"><i class="ti ti-filter"></i> Filter</button>
            <a href="{{ url('/admin/laporan/restock') }}" class="laporan-btn laporan-btn-muted"><i class="ti ti-refresh"></i> Reset</a>
        </form>
    </section>

    <section class="laporan-summary summary-count-4" data-aos="fade-up" data-aos-delay="200">
        @foreach ($summaryCards as $idx => $card)
            <article class="laporan-summary-card {{ $card['class'] }}" data-aos="zoom-in" data-aos-delay="{{ 80 + ($idx * 100) }}">
                <span class="laporan-summary-icon"><i class="{{ $card['icon'] }}"></i></span>
                <div>
                    <small>{{ $card['label'] }}</small>
                    <strong>{{ $card['value'] }}</strong>
                    <p>{{ $card['desc'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="laporan-chart-grid" data-aos="zoom-in" data-aos-delay="250">
        <article class="laporan-chart-card laporan-chart-wide">
            <div class="laporan-card-head">
                <div>
                    <span>Grafik</span>
                    <h3>Top Restock Barang</h3>
                </div>
            </div>

            @if (empty($restockByBarang))
                <div class="laporan-empty is-chart">
                    <span><i class="ti ti-package-off"></i></span>
                    <h4>Belum ada restock barang</h4>
                </div>
            @else
                <div class="laporan-chart-wrap"><canvas id="laporanTopProductChart"></canvas></div>
            @endif
        </article>

        <article class="laporan-panel">
            <div class="laporan-card-head">
                <div>
                    <span>Produk</span>
                    <h3>Barang Paling Sering Direstock</h3>
                </div>
            </div>

            @if ($topBarang === null)
                <div class="laporan-empty">
                    <span><i class="ti ti-package-off"></i></span>
                    <h4>Belum ada barang</h4>
                </div>
            @else
                <div class="laporan-method-list">
                    <div class="laporan-method-item method-cash">
                        <span><i class="ti ti-package-import"></i></span>
                        <div>
                            <strong>{{ $topBarang['nama_barang'] ?? '-' }}</strong>
                            <small>{{ $topBarang['kode_barang'] ?? '-' }}</small>
                        </div>
                        <b>{{ number_format($topBarang['total_qty'] ?? 0, 0, ',', '.') }} {{ $topBarang['satuan'] ?? '' }}</b>
                    </div>
                    <div class="laporan-method-item method-qris">
                        <span><i class="ti ti-cash"></i></span>
                        <div>
                            <strong>Total Nilai</strong>
                            <small>Modal restock barang</small>
                        </div>
                        <b>{{ app_rupiah($topBarang['total_nilai'] ?? 0) }}</b>
                    </div>
                    <div class="laporan-method-item method-ewallet">
                        <span><i class="ti ti-calculator"></i></span>
                        <div>
                            <strong>Rata Harga Beli</strong>
                            <small>Harga beli rata-rata</small>
                        </div>
                        <b>{{ app_rupiah($topBarang['rata_harga_beli'] ?? 0) }}</b>
                    </div>
                </div>
            @endif
        </article>

        <article class="laporan-panel">
            <div class="laporan-card-head">
                <div>
                    <span>Supplier</span>
                    <h3>Supplier Teraktif</h3>
                </div>
            </div>

            @if ($topSupplier === null)
                <div class="laporan-empty">
                    <span><i class="ti ti-truck-off"></i></span>
                    <h4>Belum ada supplier</h4>
                </div>
            @else
                <div class="laporan-method-list">
                    <div class="laporan-method-item method-transfer">
                        <span><i class="ti ti-truck-delivery"></i></span>
                        <div>
                            <strong>{{ $topSupplier['nama_supplier'] ?? '-' }}</strong>
                            <small>{{ $topSupplier['total_restock'] ?? 0 }} transaksi restock</small>
                        </div>
                        <b>{{ number_format($topSupplier['total_qty'] ?? 0, 0, ',', '.') }} item</b>
                    </div>
                    <div class="laporan-method-item method-qris">
                        <span><i class="ti ti-cash"></i></span>
                        <div>
                            <strong>Total Nilai</strong>
                            <small>Nilai pembelian supplier</small>
                        </div>
                        <b>{{ app_rupiah($topSupplier['total_nilai'] ?? 0) }}</b>
                    </div>
                </div>
            @endif
        </article>
    </section>

    <section class="laporan-grid" data-aos="fade-up" data-aos-delay="300">
        <article class="laporan-panel">
            <div class="laporan-card-head">
                <div>
                    <span>Barang</span>
                    <h3>Restock Per Barang</h3>
                </div>
            </div>

            @if (empty($restockByBarang))
                <div class="laporan-empty">
                    <span><i class="ti ti-package-off"></i></span>
                    <h4>Belum ada restock barang</h4>
                </div>
            @else
                <div class="laporan-table-wrap">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Barang</th>
                                <th>Qty Masuk</th>
                                <th>Total Nilai</th>
                                <th>Rata Harga Beli</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($restockByBarang as $idx => $row)
                                <tr>
                                    <td><strong class="laporan-number">#{{ $idx + 1 }}</strong></td>
                                    <td>
                                        <div class="laporan-product">
                                            <span>{{ $idx + 1 }}</span>
                                            <div>
                                                <strong>{{ $row['nama_barang'] ?? '-' }}</strong>
                                                <small>{{ $row['kode_barang'] ?? '-' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="laporan-number">
                                            {{ number_format($row['total_qty'] ?? 0, 0, ',', '.') }} {{ $row['satuan'] ?? '' }}
                                        </strong>
                                    </td>
                                    <td><strong class="laporan-money">{{ app_rupiah($row['total_nilai'] ?? 0) }}</strong></td>
                                    <td><strong class="laporan-money is-muted">{{ app_rupiah($row['rata_harga_beli'] ?? 0) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>

        <article class="laporan-panel">
            <div class="laporan-card-head">
                <div>
                    <span>Supplier</span>
                    <h3>Restock Per Supplier</h3>
                </div>
            </div>

            @if (empty($restockBySupplier))
                <div class="laporan-empty">
                    <span><i class="ti ti-truck-off"></i></span>
                    <h4>Belum ada restock supplier</h4>
                </div>
            @else
                <div class="laporan-table-wrap">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Supplier</th>
                                <th>Total Restock</th>
                                <th>Total Qty</th>
                                <th>Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($restockBySupplier as $idx => $row)
                                <tr>
                                    <td><strong class="laporan-number">#{{ $idx + 1 }}</strong></td>
                                    <td>
                                        <div class="laporan-product">
                                            <span><i class="ti ti-truck-delivery"></i></span>
                                            <div>
                                                <strong>{{ $row['nama_supplier'] ?? '-' }}</strong>
                                                <small>Supplier restock</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong class="laporan-number">{{ $row['total_restock'] ?? 0 }}</strong></td>
                                    <td>
                                        <strong class="laporan-number">{{ number_format($row['total_qty'] ?? 0, 0, ',', '.') }}</strong>
                                    </td>
                                    <td><strong class="laporan-money">{{ app_rupiah($row['total_nilai'] ?? 0) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>
</div>

<script type="application/json" id="laporanChartData">
    {!! json_encode([
        'sales' => ['labels' => [], 'penjualan' => [], 'laba' => []],
        'payments' => ['labels' => [], 'values' => []],
        'products' => $chartRestockBarang,
        'suppliers' => $chartRestockSupplier,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection
