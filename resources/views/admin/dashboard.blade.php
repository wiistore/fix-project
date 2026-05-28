@php
    $pageCss = ['assets/css/dashboard.css'];
    $pageScript = 'dashboard';
    $useChart = true;

    $totalBarang = (int) ($totalBarang ?? 0);
    $totalTransaksiHariIni = (int) ($totalTransaksiHariIni ?? 0);
    $totalPenjualanHariIni = (float) ($totalPenjualanHariIni ?? 0);
    $stokMenipis = (int) ($stokMenipis ?? 0);
    $transaksiTerbaru = $transaksiTerbaru ?? [];

    $chartPenjualan7Hari = $chartPenjualan7Hari ?? ['labels' => [], 'values' => []];
    $chartTopBarang = $chartTopBarang ?? ['labels' => [], 'values' => []];
    $chartStatusStok = $chartStatusStok ?? ['labels' => ['Aman','Menipis','Habis'], 'values' => [0,0,0]];

    $dashboardCharts = [
        'sales' => [
            'labels' => array_values($chartPenjualan7Hari['labels'] ?? []),
            'values' => array_map('floatval', array_values($chartPenjualan7Hari['values'] ?? [])),
        ],
        'topProducts' => [
            'labels' => array_values($chartTopBarang['labels'] ?? []),
            'values' => array_map('intval', array_values($chartTopBarang['values'] ?? [])),
        ],
        'stockStatus' => [
            'labels' => array_values($chartStatusStok['labels'] ?? ['Aman','Menipis','Habis']),
            'values' => array_map('intval', array_values($chartStatusStok['values'] ?? [0,0,0])),
        ],
    ];

    $userName = app_user_name($user ?? null);
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Dashboard Admin',
    'activeMenu' => $activeMenu ?? 'dashboard',
    'pageCss' => $pageCss,
    'pageScript' => $pageScript,
    'useChart' => $useChart,
])

@section('content')
<div class="dashboard-page">
    <section class="dashboard-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="dashboard-hero-content">
            <span class="dashboard-eyebrow">
                <i class="ti ti-sparkles"></i>
                Ringkasan Hari Ini
            </span>

            <h2>Halo, {{ $userName }}</h2>

            <p>
                Pantau barang, transaksi, stok menipis, dan performa koperasi dari satu halaman dashboard.
            </p>
        </div>

        <div class="dashboard-hero-actions">
            <a href="{{ url('/admin/transaksi') }}" class="dashboard-action dashboard-action-primary">
                <i class="ti ti-shopping-cart-plus"></i>
                Mulai Transaksi
            </a>

            <a href="{{ url('/admin/laporan') }}" class="dashboard-action dashboard-action-soft">
                <i class="ti ti-report-analytics"></i>
                Lihat Laporan
            </a>
        </div>
    </section>

    <section class="dashboard-stats">
        <a href="{{ url('/admin/barang') }}" class="dashboard-stat-card stat-green" data-aos="flip-left" data-aos-delay="80">
            <span class="dashboard-stat-icon">
                <i class="ti ti-package"></i>
            </span>
            <div>
                <small>Total Barang</small>
                <strong data-counter="{{ $totalBarang }}" data-counter-format="thousand">0</strong>
                <p>Semua barang terdata</p>
            </div>
        </a>

        <a href="{{ url('/admin/riwayat-transaksi') }}" class="dashboard-stat-card stat-blue" data-aos="flip-right" data-aos-delay="160">
            <span class="dashboard-stat-icon">
                <i class="ti ti-receipt"></i>
            </span>
            <div>
                <small>Transaksi Hari Ini</small>
                <strong data-counter="{{ $totalTransaksiHariIni }}" data-counter-format="thousand">0</strong>
                <p>Jumlah transaksi masuk</p>
            </div>
        </a>

        <a href="{{ url('/admin/laporan') }}" class="dashboard-stat-card stat-orange" data-aos="flip-left" data-aos-delay="240">
            <span class="dashboard-stat-icon">
                <i class="ti ti-cash"></i>
            </span>
            <div>
                <small>Penjualan Hari Ini</small>
                <strong data-counter="{{ $totalPenjualanHariIni }}" data-counter-prefix="Rp " data-counter-format="rupiah">Rp 0</strong>
                <p>Total omzet hari ini</p>
            </div>
        </a>

        <a href="{{ url('/admin/barang') }}" class="dashboard-stat-card stat-red" data-aos="flip-right" data-aos-delay="320">
            <span class="dashboard-stat-icon">
                <i class="ti ti-alert-triangle"></i>
            </span>
            <div>
                <small>Stok Menipis</small>
                <strong data-counter="{{ $stokMenipis }}" data-counter-format="thousand">0</strong>
                <p>Perlu dicek/restock</p>
            </div>
        </a>
    </section>

    <section class="dashboard-grid">
        <article class="dashboard-card dashboard-card-large" data-aos="zoom-in-up" data-aos-delay="100">
            <div class="dashboard-card-header">
                <div>
                    <span>Grafik Penjualan</span>
                    <h3>Penjualan 7 Hari Terakhir</h3>
                </div>

                <span class="dashboard-badge">
                    <i class="ti ti-trending-up"></i>
                    Mingguan
                </span>
            </div>

            <div class="dashboard-chart chart-large">
                <canvas id="salesChart"></canvas>
            </div>
        </article>

        <article class="dashboard-card" data-aos="zoom-in-down" data-aos-delay="200">
            <div class="dashboard-card-header">
                <div>
                    <span>Status Stok</span>
                    <h3>Kondisi Barang</h3>
                </div>

                <span class="dashboard-badge badge-blue">
                    <i class="ti ti-package-import"></i>
                    Aktif
                </span>
            </div>

            <div class="dashboard-chart chart-doughnut">
                <canvas id="stockChart"></canvas>
            </div>

            <div class="dashboard-legend">
                <span><i class="legend-green"></i>Aman</span>
                <span><i class="legend-orange"></i>Menipis</span>
                <span><i class="legend-red"></i>Habis</span>
            </div>
        </article>
    </section>

    <section class="dashboard-grid dashboard-grid-bottom">
        <article class="dashboard-card" data-aos="fade-up-right" data-aos-delay="100">
            <div class="dashboard-card-header">
                <div>
                    <span>Produk Populer</span>
                    <h3>Top Barang Terlaris</h3>
                </div>

                <a href="{{ url('/admin/laporan') }}" class="dashboard-card-link">
                    Detail
                    <i class="ti ti-arrow-right"></i>
                </a>
            </div>

            <div class="dashboard-chart chart-bar">
                <canvas id="topProductChart"></canvas>
            </div>
        </article>

        <article class="dashboard-card" data-aos="fade-up-left" data-aos-delay="200">
            <div class="dashboard-card-header">
                <div>
                    <span>Aktivitas</span>
                    <h3>Transaksi Terbaru</h3>
                </div>

                <a href="{{ url('/admin/riwayat-transaksi') }}" class="dashboard-card-link">
                    Semua
                    <i class="ti ti-arrow-right"></i>
                </a>
            </div>

            @if (empty($transaksiTerbaru))
                <div class="dashboard-empty">
                    <i class="ti ti-receipt-off"></i>
                    <h4>Belum ada transaksi</h4>
                    <p>Transaksi terbaru akan muncul di sini setelah ada penjualan.</p>
                </div>
            @else
                <div class="dashboard-transactions">
                    @foreach ($transaksiTerbaru as $transaksi)
                        <a href="{{ url('/admin/riwayat-transaksi') }}" class="dashboard-transaction-item">
                            <span class="dashboard-transaction-icon">
                                <i class="ti ti-receipt-2"></i>
                            </span>

                            <span class="dashboard-transaction-main">
                                <strong>{{ $transaksi['kode_transaksi'] ?? '-' }}</strong>
                                <small>
                                    {{ app_date($transaksi['tanggal'] ?? '') }}
                                    •
                                    {{ $transaksi['kasir'] ?? '-' }}
                                </small>
                            </span>

                            <span class="dashboard-transaction-side">
                                <strong>{{ app_rupiah($transaksi['total_jual'] ?? 0) }}</strong>
                                <small>{{ strtoupper((string) ($transaksi['metode_bayar'] ?? '-')) }}</small>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </article>
    </section>

    <section class="dashboard-shortcuts" data-aos="fade-up" data-aos-delay="100">
        <a href="{{ url('/admin/barang/create') }}" class="dashboard-shortcut">
            <i class="ti ti-package"></i>
            <span>Tambah Barang</span>
        </a>

        <a href="{{ url('/admin/restock/create') }}" class="dashboard-shortcut">
            <i class="ti ti-stack-push"></i>
            <span>Restock Barang</span>
        </a>

        <a href="{{ url('/admin/transaksi') }}" class="dashboard-shortcut">
            <i class="ti ti-shopping-cart"></i>
            <span>Transaksi POS</span>
        </a>

        <a href="{{ url('/admin/laporan') }}" class="dashboard-shortcut">
            <i class="ti ti-file-analytics"></i>
            <span>Cetak Laporan</span>
        </a>
    </section>
</div>

<script type="application/json" id="dashboardChartData">
    {!! json_encode($dashboardCharts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection
