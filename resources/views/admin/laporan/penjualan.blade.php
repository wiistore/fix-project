@php
    $pageCss = ['assets/css/laporan.css'];
    $pageScripts = ['assets/js/laporan.js'];
    $useChart = true;

    $summary = $summary ?? [];
    $penjualanHarian = $penjualanHarian ?? [];
    $penjualanKasir = $penjualanKasir ?? [];
    $metodePembayaran = $metodePembayaran ?? [];
    $tanggalMulai = $tanggalMulai ?? '';
    $tanggalSelesai = $tanggalSelesai ?? '';

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
        ['class' => 'summary-green', 'icon' => 'ti ti-receipt', 'label' => 'Total Transaksi', 'value' => $totalTransaksi, 'desc' => 'Transaksi penjualan'],
        ['class' => 'summary-blue', 'icon' => 'ti ti-cash', 'label' => 'Total Penjualan', 'value' => app_rupiah($totalPenjualan), 'desc' => 'Omzet dalam periode'],
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
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Laporan Penjualan',
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
                <i class="ti ti-cash"></i>
                Laporan Penjualan
            </span>
            <h2>Penjualan</h2>
            <p>Pantau omzet, modal, laba, performa harian, kontribusi kasir, dan metode pembayaran.</p>
        </div>

        <div class="laporan-hero-actions">
            <a href="{{ $exportUrl('/admin/laporan/export/penjualan') }}" class="laporan-btn laporan-btn-primary">
                <i class="ti ti-file-spreadsheet"></i>
                Export Excel
            </a>
            <a href="{{ url('/admin/laporan') }}" class="laporan-btn laporan-btn-soft">
                <i class="ti ti-layout-dashboard"></i>
                Ringkasan
            </a>
        </div>
    </section>

    <section class="laporan-nav" data-aos="fade-up" data-aos-delay="100">
        <a href="{{ url('/admin/laporan') }}"><i class="ti ti-layout-dashboard"></i> Ringkasan</a>
        <a href="{{ url('/admin/laporan/penjualan') }}" class="is-active"><i class="ti ti-cash"></i> Penjualan</a>
        <a href="{{ url('/admin/laporan/laba') }}"><i class="ti ti-chart-line"></i> Laba</a>
        <a href="{{ url('/admin/laporan/barang-terlaris') }}"><i class="ti ti-award"></i> Barang Terlaris</a>
        <a href="{{ url('/admin/laporan/restock') }}"><i class="ti ti-stack-push"></i> Restock</a>
    </section>

    <section class="laporan-filter-panel" data-aos="fade-up" data-aos-delay="150">
        <div>
            <span>Filter Periode</span>
            <h3>Atur Rentang Tanggal</h3>
        </div>

        <form action="{{ url('/admin/laporan/penjualan') }}" method="GET" class="laporan-filter-form">
            <label>
                <span>Tanggal Mulai</span>
                <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}">
            </label>
            <label>
                <span>Tanggal Selesai</span>
                <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai }}">
            </label>
            <button type="submit" class="laporan-btn laporan-btn-ghost"><i class="ti ti-filter"></i> Filter</button>
            <a href="{{ url('/admin/laporan/penjualan') }}" class="laporan-btn laporan-btn-muted"><i class="ti ti-refresh"></i> Reset</a>
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
                    <h3>Penjualan & Laba Harian</h3>
                </div>
            </div>

            @if (empty($penjualanHarian))
                <div class="laporan-empty is-chart">
                    <span><i class="ti ti-chart-line"></i></span>
                    <h4>Belum ada data penjualan</h4>
                </div>
            @else
                <div class="laporan-chart-wrap"><canvas id="laporanSalesChart"></canvas></div>
            @endif
        </article>

        <article class="laporan-chart-card">
            <div class="laporan-card-head">
                <div>
                    <span>Pembayaran</span>
                    <h3>Metode Pembayaran</h3>
                </div>
            </div>

            @if (empty($metodePembayaran))
                <div class="laporan-empty is-chart">
                    <span><i class="ti ti-credit-card-off"></i></span>
                    <h4>Belum ada pembayaran</h4>
                </div>
            @else
                <div class="laporan-chart-wrap chart-small"><canvas id="laporanPaymentChart"></canvas></div>
            @endif
        </article>

        <article class="laporan-panel">
            <div class="laporan-card-head">
                <div>
                    <span>Ringkasan</span>
                    <h3>Metode Bayar</h3>
                </div>
            </div>

            @if (empty($metodePembayaran))
                <div class="laporan-empty">
                    <span><i class="ti ti-wallet-off"></i></span>
                    <h4>Belum ada data</h4>
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
                    <span>Kasir</span>
                    <h3>Penjualan Per Kasir</h3>
                </div>
            </div>

            @if (empty($penjualanKasir))
                <div class="laporan-empty">
                    <span><i class="ti ti-user-off"></i></span>
                    <h4>Belum ada data kasir</h4>
                </div>
            @else
                <div class="laporan-table-wrap">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th>Kasir</th>
                                <th>Transaksi</th>
                                <th>Penjualan</th>
                                <th>Modal</th>
                                <th>Laba</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($penjualanKasir as $idx => $row)
                                <tr>
                                    <td>
                                        <div class="laporan-product">
                                            <span>{{ $idx + 1 }}</span>
                                            <div>
                                                <strong>{{ $row['nama_kasir'] ?? '-' }}</strong>
                                                <small>Kasir transaksi</small>
                                            </div>
                                        </div>
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
    </section>
</div>

<script type="application/json" id="laporanChartData">
    {!! json_encode([
        'sales' => $chartPenjualan,
        'payments' => $chartMetode,
        'products' => ['labels' => [], 'values' => []],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection
