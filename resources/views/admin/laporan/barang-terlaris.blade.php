@php
    $pageCss = ['assets/css/laporan.css'];
    $pageScripts = ['assets/js/laporan.js'];
    $useChart = true;

    $barangTerlaris = $barangTerlaris ?? [];
    $tanggalMulai = $tanggalMulai ?? '';
    $tanggalSelesai = $tanggalSelesai ?? '';

    $exportUrl = function (string $path) use ($tanggalMulai, $tanggalSelesai) {
        $q = [];
        if ($tanggalMulai !== '') $q['tanggal_mulai'] = $tanggalMulai;
        if ($tanggalSelesai !== '') $q['tanggal_selesai'] = $tanggalSelesai;
        return url($path).(empty($q) ? '' : '?'.http_build_query($q));
    };

    $totalJenisBarang = count($barangTerlaris);
    $totalQty = 0; $totalPenjualan = 0; $totalModal = 0; $totalLaba = 0;
    foreach ($barangTerlaris as $row) {
        $totalQty += (int) ($row['total_qty'] ?? 0);
        $totalPenjualan += (float) ($row['total_penjualan'] ?? 0);
        $totalModal += (float) ($row['total_modal'] ?? 0);
        $totalLaba += (float) ($row['total_laba'] ?? 0);
    }

    $topBarang = $barangTerlaris[0] ?? null;
    $topBarangNama = $topBarang['nama_barang'] ?? '-';
    $topBarangQty = (int) ($topBarang['total_qty'] ?? 0);
    $marginLaba = $totalPenjualan > 0 ? ($totalLaba / $totalPenjualan) * 100 : 0;

    $summaryCards = [
        ['class' => 'summary-green', 'icon' => 'ti ti-award', 'label' => 'Jenis Terjual', 'value' => $totalJenisBarang, 'desc' => 'Barang masuk laporan'],
        ['class' => 'summary-blue', 'icon' => 'ti ti-packages', 'label' => 'Total Qty', 'value' => number_format($totalQty, 0, ',', '.'), 'desc' => 'Akumulasi barang terjual'],
        ['class' => 'summary-orange', 'icon' => 'ti ti-cash', 'label' => 'Total Penjualan', 'value' => app_rupiah($totalPenjualan), 'desc' => 'Omzet dari barang'],
        ['class' => 'summary-purple', 'icon' => 'ti ti-chart-line', 'label' => 'Total Laba', 'value' => app_rupiah($totalLaba), 'desc' => number_format($marginLaba, 1, ',', '.').'% margin'],
    ];

    $chartBarangQty = ['labels' => [], 'values' => [], 'datasetLabel' => 'Qty Terjual', 'tooltipMode' => 'number'];
    foreach (array_slice($barangTerlaris, 0, 10) as $row) {
        $chartBarangQty['labels'][] = (string) ($row['nama_barang'] ?? '-');
        $chartBarangQty['values'][] = (int) ($row['total_qty'] ?? 0);
    }
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Laporan Barang Terlaris',
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
                <i class="ti ti-award"></i>
                Laporan Barang Terlaris
            </span>
            <h2>Barang Terlaris</h2>
            <p>Lihat barang paling sering terjual, total qty, omzet, modal, dan laba per barang.</p>
        </div>

        <div class="laporan-hero-actions">
            <a href="{{ $exportUrl('/admin/laporan/export/barang-terlaris') }}" class="laporan-btn laporan-btn-primary">
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
        <a href="{{ url('/admin/laporan/penjualan') }}"><i class="ti ti-cash"></i> Penjualan</a>
        <a href="{{ url('/admin/laporan/laba') }}"><i class="ti ti-chart-line"></i> Laba</a>
        <a href="{{ url('/admin/laporan/barang-terlaris') }}" class="is-active"><i class="ti ti-award"></i> Barang Terlaris</a>
        <a href="{{ url('/admin/laporan/restock') }}"><i class="ti ti-stack-push"></i> Restock</a>
    </section>

    <section class="laporan-filter-panel" data-aos="fade-up" data-aos-delay="150">
        <div>
            <span>Filter Periode</span>
            <h3>Atur Rentang Tanggal</h3>
        </div>

        <form action="{{ url('/admin/laporan/barang-terlaris') }}" method="GET" class="laporan-filter-form">
            <label>
                <span>Tanggal Mulai</span>
                <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}">
            </label>
            <label>
                <span>Tanggal Selesai</span>
                <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai }}">
            </label>
            <button type="submit" class="laporan-btn laporan-btn-ghost"><i class="ti ti-filter"></i> Filter</button>
            <a href="{{ url('/admin/laporan/barang-terlaris') }}" class="laporan-btn laporan-btn-muted"><i class="ti ti-refresh"></i> Reset</a>
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
                    <h3>Top Barang Berdasarkan Qty</h3>
                </div>
            </div>

            @if (empty($barangTerlaris))
                <div class="laporan-empty is-chart">
                    <span><i class="ti ti-award-off"></i></span>
                    <h4>Belum ada barang terjual</h4>
                </div>
            @else
                <div class="laporan-chart-wrap"><canvas id="laporanTopProductChart"></canvas></div>
            @endif
        </article>

        <article class="laporan-panel">
            <div class="laporan-card-head">
                <div>
                    <span>Juara</span>
                    <h3>Barang Paling Laris</h3>
                </div>
            </div>

            @if ($topBarang === null)
                <div class="laporan-empty">
                    <span><i class="ti ti-package-off"></i></span>
                    <h4>Belum ada juara</h4>
                </div>
            @else
                <div class="laporan-method-list">
                    <div class="laporan-method-item method-cash">
                        <span><i class="ti ti-award"></i></span>
                        <div>
                            <strong>{{ $topBarangNama }}</strong>
                            <small>{{ $topBarang['kode_barang'] ?? '-' }} • {{ $topBarang['nama_kategori'] ?? '-' }}</small>
                        </div>
                        <b>{{ number_format($topBarangQty, 0, ',', '.') }} {{ $topBarang['satuan'] ?? '' }}</b>
                    </div>
                    <div class="laporan-method-item method-qris">
                        <span><i class="ti ti-cash"></i></span>
                        <div>
                            <strong>Total Penjualan</strong>
                            <small>Omzet barang teratas</small>
                        </div>
                        <b>{{ app_rupiah($topBarang['total_penjualan'] ?? 0) }}</b>
                    </div>
                    <div class="laporan-method-item method-ewallet">
                        <span><i class="ti ti-chart-line"></i></span>
                        <div>
                            <strong>Total Laba</strong>
                            <small>Laba barang teratas</small>
                        </div>
                        <b>{{ app_rupiah($topBarang['total_laba'] ?? 0) }}</b>
                    </div>
                </div>
            @endif
        </article>
    </section>

    <section class="laporan-panel">
        <div class="laporan-card-head">
            <div>
                <span>Ranking</span>
                <h3>Daftar Barang Terlaris</h3>
            </div>
            <a href="{{ $exportUrl('/admin/laporan/export/barang-terlaris') }}" class="laporan-mini-link">
                <i class="ti ti-file-spreadsheet"></i> Excel
            </a>
        </div>

        @if (empty($barangTerlaris))
            <div class="laporan-empty">
                <span><i class="ti ti-package-off"></i></span>
                <h4>Belum ada data barang terlaris</h4>
            </div>
        @else
            <div class="laporan-table-wrap">
                <table class="laporan-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Barang</th>
                            <th>Barcode</th>
                            <th>Kategori</th>
                            <th>Qty Terjual</th>
                            <th>Penjualan</th>
                            <th>Modal</th>
                            <th>Laba</th>
                            <th>Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($barangTerlaris as $idx => $row)
                            @php
                                $rp = (float) ($row['total_penjualan'] ?? 0);
                                $rm = (float) ($row['total_modal'] ?? 0);
                                $rl = (float) ($row['total_laba'] ?? 0);
                                $rmg = $rp > 0 ? ($rl / $rp) * 100 : 0;
                            @endphp
                            <tr>
                                <td><span class="laporan-number">#{{ $idx + 1 }}</span></td>
                                <td>
                                    <div class="laporan-product">
                                        <span>{{ $idx + 1 }}</span>
                                        <div>
                                            <strong>{{ $row['nama_barang'] ?? '-' }}</strong>
                                            <small>{{ $row['kode_barang'] ?? '-' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ ($row['barcode'] ?? '') !== '' ? $row['barcode'] : '-' }}</td>
                                <td>{{ $row['nama_kategori'] ?? '-' }}</td>
                                <td>
                                    <strong class="laporan-number">
                                        {{ number_format($row['total_qty'] ?? 0, 0, ',', '.') }} {{ $row['satuan'] ?? '' }}
                                    </strong>
                                </td>
                                <td><strong class="laporan-money">{{ app_rupiah($rp) }}</strong></td>
                                <td><strong class="laporan-money is-muted">{{ app_rupiah($rm) }}</strong></td>
                                <td>
                                    <strong class="laporan-money {{ $rl >= 0 ? 'is-profit' : 'is-loss' }}">
                                        {{ app_rupiah($rl) }}
                                    </strong>
                                </td>
                                <td>
                                    <strong class="laporan-number">{{ number_format($rmg, 1, ',', '.') }}%</strong>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>

<script type="application/json" id="laporanChartData">
    {!! json_encode([
        'sales' => ['labels' => [], 'penjualan' => [], 'laba' => []],
        'payments' => ['labels' => [], 'values' => []],
        'products' => $chartBarangQty,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection
