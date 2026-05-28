@php
    $pageCss = ['assets/css/laporan.css'];
    $pageScripts = ['assets/js/laporan.js'];
    $useChart = true;

    $summary = $summary ?? [];
    $penjualanHarian = $penjualanHarian ?? [];
    $barangTerlaris = $barangTerlaris ?? [];
    $metodePembayaran = $metodePembayaran ?? [];
    $stokMenipisData = $stokMenipis ?? [];
    $transaksiBatal = $transaksiBatal ?? [];
    $flash = $flash ?? [];
    $tanggalMulai = $tanggalMulai ?? '';
    $tanggalSelesai = $tanggalSelesai ?? '';
    $success = $flash['success'] ?? null;
    $error = $flash['error'] ?? null;

    $methodLabel = fn ($m) => match (strtolower((string) $m)) {
        'cash' => 'Cash', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'ewallet' => 'E-Wallet',
        default => ucfirst((string) $m),
    };
    $methodIcon = fn ($m) => match (strtolower((string) $m)) {
        'cash' => 'ti ti-cash', 'qris' => 'ti ti-qrcode', 'transfer' => 'ti ti-building-bank',
        'ewallet' => 'ti ti-wallet', default => 'ti ti-credit-card',
    };
    $exportUrl = function (string $path) use ($tanggalMulai, $tanggalSelesai) {
        $q = [];
        if ($tanggalMulai !== '') $q['tanggal_mulai'] = $tanggalMulai;
        if ($tanggalSelesai !== '') $q['tanggal_selesai'] = $tanggalSelesai;
        return url($path).(empty($q) ? '' : '?'.http_build_query($q));
    };

    $totalTransaksi = (int) ($summary['total_transaksi'] ?? 0);
    $totalPenjualan = (float) ($summary['total_penjualan'] ?? 0);
    $totalModal = (float) ($summary['total_modal'] ?? 0);
    $totalLaba = (float) ($summary['total_laba'] ?? 0);
    $marginLaba = $totalPenjualan > 0 ? ($totalLaba / $totalPenjualan) * 100 : 0;

    $summaryCards = [
        ['class' => 'summary-green', 'icon' => 'ti ti-receipt', 'label' => 'Total Transaksi', 'value' => $totalTransaksi, 'desc' => 'Transaksi dalam periode'],
        ['class' => 'summary-blue', 'icon' => 'ti ti-cash', 'label' => 'Total Penjualan', 'value' => app_rupiah($totalPenjualan), 'desc' => 'Omzet kotor'],
        ['class' => 'summary-orange', 'icon' => 'ti ti-wallet', 'label' => 'Total Modal', 'value' => app_rupiah($totalModal), 'desc' => 'Harga beli barang'],
        ['class' => 'summary-purple', 'icon' => 'ti ti-chart-line', 'label' => 'Total Laba', 'value' => app_rupiah($totalLaba), 'desc' => number_format($marginLaba, 1, ',', '.').'% margin'],
    ];

    $chartPenjualan = ['labels' => [], 'penjualan' => [], 'laba' => []];
    foreach ($penjualanHarian as $row) {
        $chartPenjualan['labels'][] = app_date($row['tanggal'] ?? '', 'd M Y');
        $chartPenjualan['penjualan'][] = (float) ($row['total_penjualan'] ?? 0);
        $chartPenjualan['laba'][] = (float) ($row['total_laba'] ?? 0);
    }

    $chartMetode = ['labels' => [], 'values' => []];
    foreach ($metodePembayaran as $row) {
        $chartMetode['labels'][] = $methodLabel($row['metode_bayar'] ?? '-');
        $chartMetode['values'][] = (float) ($row['total_penjualan'] ?? 0);
    }

    $chartBarang = ['labels' => [], 'values' => []];
    foreach (array_slice($barangTerlaris, 0, 6) as $row) {
        $chartBarang['labels'][] = (string) ($row['nama_barang'] ?? '-');
        $chartBarang['values'][] = (int) ($row['total_qty'] ?? 0);
    }
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Laporan',
    'activeMenu' => $activeMenu ?? 'laporan',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
    'useChart' => $useChart,
])

@section('content')
<div class="laporan-page">
    @if ($success)
        <div class="laporan-alert laporan-alert-success">
            <i class="ti ti-circle-check"></i>
            <span>{{ $success }}</span>
        </div>
    @endif

    @if ($error)
        <div class="laporan-alert laporan-alert-error">
            <i class="ti ti-alert-triangle"></i>
            <span>{{ $error }}</span>
        </div>
    @endif

    <section class="laporan-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="laporan-hero-content">
            <span class="laporan-eyebrow">
                <i class="ti ti-chart-bar"></i>
                Ringkasan Laporan
            </span>
            <h2>Laporan</h2>
        </div>

        <div class="laporan-hero-actions">
            <a href="{{ $exportUrl('/admin/laporan/export/ringkasan') }}" class="laporan-btn laporan-btn-primary">
                <i class="ti ti-file-spreadsheet"></i>
                Export Excel
            </a>
            <a href="{{ url('/admin/riwayat-transaksi') }}" class="laporan-btn laporan-btn-soft">
                <i class="ti ti-history"></i>
                Riwayat
            </a>
        </div>
    </section>

    <section class="laporan-nav" data-aos="fade-up" data-aos-delay="100">
        <a href="{{ url('/admin/laporan') }}" class="is-active"><i class="ti ti-layout-dashboard"></i> Ringkasan</a>
        <a href="{{ url('/admin/laporan/penjualan') }}"><i class="ti ti-cash"></i> Penjualan</a>
        <a href="{{ url('/admin/laporan/laba') }}"><i class="ti ti-chart-line"></i> Laba</a>
        <a href="{{ url('/admin/laporan/barang-terlaris') }}"><i class="ti ti-award"></i> Barang Terlaris</a>
        <a href="{{ url('/admin/laporan/restock') }}"><i class="ti ti-stack-push"></i> Restock</a>
    </section>

    <section class="laporan-filter-panel" data-aos="fade-up" data-aos-delay="150">
        <div>
            <span>Filter Periode</span>
            <h3>Atur Rentang Tanggal</h3>
        </div>

        <form action="{{ url('/admin/laporan') }}" method="GET" class="laporan-filter-form">
            <label>
                <span>Tanggal Mulai</span>
                <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}">
            </label>
            <label>
                <span>Tanggal Selesai</span>
                <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai }}">
            </label>
            <button type="submit" class="laporan-btn laporan-btn-ghost">
                <i class="ti ti-filter"></i> Filter
            </button>
            <a href="{{ url('/admin/laporan') }}" class="laporan-btn laporan-btn-muted">
                <i class="ti ti-refresh"></i> Reset
            </a>
        </form>
    </section>

    <section class="laporan-summary summary-count-{{ count($summaryCards) }}" data-aos="fade-up" data-aos-delay="200">
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
                    <h3>Penjualan & Laba Harian</h3>
                </div>
                <a href="{{ $exportUrl('/admin/laporan/export/penjualan') }}" class="laporan-mini-link">
                    <i class="ti ti-file-spreadsheet"></i> Export
                </a>
            </div>

            @if (empty($penjualanHarian))
                <div class="laporan-empty is-chart">
                    <span><i class="ti ti-chart-line"></i></span>
                    <h4>Belum ada data penjualan</h4>
                    <p>Transaksi belum ada di periode ini.</p>
                </div>
            @else
                <div class="laporan-chart-wrap">
                    <canvas id="laporanSalesChart"></canvas>
                </div>
            @endif
        </article>

        <article class="laporan-chart-card">
            <div class="laporan-card-head">
                <div>
                    <span>Metode</span>
                    <h3>Pembayaran</h3>
                </div>
            </div>

            @if (empty($metodePembayaran))
                <div class="laporan-empty is-chart">
                    <span><i class="ti ti-wallet-off"></i></span>
                    <h4>Belum ada pembayaran</h4>
                    <p>Metode pembayaran belum tercatat.</p>
                </div>
            @else
                <div class="laporan-chart-wrap chart-small">
                    <canvas id="laporanPaymentChart"></canvas>
                </div>
            @endif
        </article>

        <article class="laporan-chart-card">
            <div class="laporan-card-head">
                <div>
                    <span>Produk</span>
                    <h3>Top Barang</h3>
                </div>
            </div>

            @if (empty($barangTerlaris))
                <div class="laporan-empty is-chart">
                    <span><i class="ti ti-award-off"></i></span>
                    <h4>Belum ada barang terjual</h4>
                    <p>Barang belum punya riwayat penjualan.</p>
                </div>
            @else
                <div class="laporan-chart-wrap chart-small">
                    <canvas id="laporanTopProductChart"></canvas>
                </div>
            @endif
        </article>
    </section>

    <section class="laporan-grid" data-aos="fade-up" data-aos-delay="300">
        <article class="laporan-panel">
            <div class="laporan-card-head">
                <div>
                    <span>Harian</span>
                    <h3>Penjualan Harian</h3>
                </div>
                <a href="{{ $exportUrl('/admin/laporan/export/penjualan') }}" class="laporan-mini-link">
                    <i class="ti ti-file-spreadsheet"></i> Excel
                </a>
            </div>

            @if (empty($penjualanHarian))
                <div class="laporan-empty">
                    <span><i class="ti ti-calendar-off"></i></span>
                    <h4>Belum ada data penjualan</h4>
                    <p>Belum ada transaksi di periode ini.</p>
                </div>
            @else
                <div class="laporan-table-wrap">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Transaksi</th>
                                <th>Penjualan</th>
                                <th>Modal</th>
                                <th>Laba</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($penjualanHarian as $row)
                                <tr>
                                    <td>
                                        <span class="laporan-date">
                                            <i class="ti ti-calendar"></i>
                                            {{ app_date($row['tanggal'] ?? '', 'd M Y') }}
                                        </span>
                                    </td>
                                    <td><strong class="laporan-number">{{ $row['total_transaksi'] ?? 0 }}</strong></td>
                                    <td><strong class="laporan-money">{{ app_rupiah($row['total_penjualan'] ?? 0) }}</strong></td>
                                    <td><strong class="laporan-money is-muted">{{ app_rupiah($row['total_modal'] ?? 0) }}</strong></td>
                                    <td><strong class="laporan-money is-profit">{{ app_rupiah($row['total_laba'] ?? 0) }}</strong></td>
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
                    <span>Produk</span>
                    <h3>Barang Terlaris</h3>
                </div>
                <a href="{{ $exportUrl('/admin/laporan/export/barang-terlaris') }}" class="laporan-mini-link">
                    <i class="ti ti-file-spreadsheet"></i> Excel
                </a>
            </div>

            @if (empty($barangTerlaris))
                <div class="laporan-empty">
                    <span><i class="ti ti-package-off"></i></span>
                    <h4>Belum ada barang terjual</h4>
                </div>
            @else
                <div class="laporan-table-wrap">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Qty</th>
                                <th>Penjualan</th>
                                <th>Laba</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_slice($barangTerlaris, 0, 10) as $idx => $row)
                                <tr>
                                    <td>
                                        <div class="laporan-product">
                                            <span>{{ $idx + 1 }}</span>
                                            <div>
                                                <strong>{{ $row['nama_barang'] ?? '-' }}</strong>
                                                <small>
                                                    {{ $row['kode_barang'] ?? '-' }}
                                                    @if (! empty($row['nama_kategori']))
                                                        • {{ $row['nama_kategori'] }}
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="laporan-number">
                                            {{ number_format($row['total_qty'] ?? 0, 0, ',', '.') }} {{ $row['satuan'] ?? '' }}
                                        </strong>
                                    </td>
                                    <td><strong class="laporan-money">{{ app_rupiah($row['total_penjualan'] ?? 0) }}</strong></td>
                                    <td><strong class="laporan-money is-profit">{{ app_rupiah($row['total_laba'] ?? 0) }}</strong></td>
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
                    <span>Pembayaran</span>
                    <h3>Metode Pembayaran</h3>
                </div>
            </div>

            @if (empty($metodePembayaran))
                <div class="laporan-empty">
                    <span><i class="ti ti-credit-card-off"></i></span>
                    <h4>Belum ada pembayaran</h4>
                </div>
            @else
                <div class="laporan-method-list">
                    @foreach ($metodePembayaran as $row)
                        @php $method = strtolower((string) ($row['metode_bayar'] ?? '-')); @endphp
                        <div class="laporan-method-item method-{{ $method }}">
                            <span><i class="{{ $methodIcon($method) }}"></i></span>
                            <div>
                                <strong>{{ $methodLabel($method) }}</strong>
                                <small>{{ $row['total_transaksi'] ?? 0 }} transaksi</small>
                            </div>
                            <b>{{ app_rupiah($row['total_penjualan'] ?? 0) }}</b>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="laporan-panel">
            <div class="laporan-card-head">
                <div>
                    <span>Inventori</span>
                    <h3>Stok Menipis</h3>
                </div>
                <a href="{{ url('/admin/barang') }}" class="laporan-mini-link">
                    <i class="ti ti-package"></i> Barang
                </a>
            </div>

            @if (empty($stokMenipisData))
                <div class="laporan-empty">
                    <span><i class="ti ti-circle-check"></i></span>
                    <h4>Stok aman</h4>
                    <p>Tidak ada barang yang masuk stok menipis.</p>
                </div>
            @else
                <div class="laporan-table-wrap">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th>Min.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stokMenipisData as $row)
                                <tr>
                                    <td>
                                        <div class="laporan-product">
                                            <span class="is-danger"><i class="ti ti-alert-triangle"></i></span>
                                            <div>
                                                <strong>{{ $row['nama_barang'] ?? '-' }}</strong>
                                                <small>{{ $row['kode_barang'] ?? '-' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $row['nama_kategori'] ?? '-' }}</td>
                                    <td>
                                        <strong class="laporan-stock is-low">
                                            {{ $row['stok'] ?? 0 }} {{ $row['satuan'] ?? '' }}
                                        </strong>
                                    </td>
                                    <td>
                                        <strong class="laporan-stock">{{ $row['stok_minimum'] ?? 0 }}</strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>

    @if (! empty($transaksiBatal))
        <section class="laporan-grid" data-aos="fade-up" data-aos-delay="350">
            <article class="laporan-panel" style="grid-column: 1 / -1;">
                <div class="laporan-card-head">
                    <div>
                        <span>Pembatalan</span>
                        <h3><i class="ti ti-x" style="color:#ef4444;"></i> Riwayat Pembatalan ({{ count($transaksiBatal) }} transaksi)</h3>
                    </div>
                </div>

                <div class="laporan-table-wrap">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Transaksi</th>
                                <th>Tanggal</th>
                                <th>Kasir</th>
                                <th>Total</th>
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transaksiBatal as $idx => $batal)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td><strong>{{ $batal['kode_transaksi'] ?? '-' }}</strong></td>
                                    <td>
                                        <span class="laporan-date">
                                            <i class="ti ti-calendar"></i>
                                            {{ app_date($batal['tanggal'] ?? '', 'd M Y') }}
                                        </span>
                                    </td>
                                    <td>{{ $batal['nama_kasir'] ?? '-' }}</td>
                                    <td>
                                        <strong class="laporan-money" style="color:#ef4444;">
                                            {{ app_rupiah($batal['total_jual'] ?? 0) }}
                                        </strong>
                                    </td>
                                    <td>{{ $batal['alasan_batal'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    @endif
</div>

<script type="application/json" id="laporanChartData">
    {!! json_encode([
        'sales' => $chartPenjualan,
        'payments' => $chartMetode,
        'products' => $chartBarang,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection
