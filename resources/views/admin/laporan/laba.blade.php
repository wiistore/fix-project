@php
    $pageCss = ['assets/css/laporan.css'];
    $pageScripts = ['assets/js/laporan.js'];
    $useChart = true;

    $summary = $summary ?? [];
    $penjualanHarian = $penjualanHarian ?? [];
    $penjualanKasir = $penjualanKasir ?? [];
    $barangTerlaris = $barangTerlaris ?? [];
    $tanggalMulai = $tanggalMulai ?? '';
    $tanggalSelesai = $tanggalSelesai ?? '';

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
    $rasioModal = $totalPenjualan > 0 ? ($totalModal / $totalPenjualan) * 100 : 0;

    $summaryCards = [
        ['class' => 'summary-green', 'icon' => 'ti ti-chart-line', 'label' => 'Total Laba', 'value' => app_rupiah($totalLaba), 'desc' => number_format($marginLaba, 1, ',', '.').'% margin'],
        ['class' => 'summary-blue', 'icon' => 'ti ti-cash', 'label' => 'Total Penjualan', 'value' => app_rupiah($totalPenjualan), 'desc' => 'Omzet kotor'],
        ['class' => 'summary-orange', 'icon' => 'ti ti-wallet', 'label' => 'Total Modal', 'value' => app_rupiah($totalModal), 'desc' => number_format($rasioModal, 1, ',', '.').'% dari omzet'],
        ['class' => 'summary-purple', 'icon' => 'ti ti-receipt', 'label' => 'Total Transaksi', 'value' => $totalTransaksi, 'desc' => 'Transaksi periode'],
    ];

    $chartPenjualan = ['labels' => [], 'penjualan' => [], 'laba' => []];
    foreach ($penjualanHarian as $row) {
        $chartPenjualan['labels'][] = app_date($row['tanggal'] ?? '', 'd M Y');
        $chartPenjualan['penjualan'][] = (float) ($row['total_penjualan'] ?? 0);
        $chartPenjualan['laba'][] = (float) ($row['total_laba'] ?? 0);
    }

    $chartBarang = ['labels' => [], 'values' => [], 'datasetLabel' => 'Laba Barang', 'tooltipMode' => 'money'];
    foreach (array_slice($barangTerlaris, 0, 8) as $row) {
        $chartBarang['labels'][] = (string) ($row['nama_barang'] ?? '-');
        $chartBarang['values'][] = (float) ($row['total_laba'] ?? 0);
    }
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Laporan Laba',
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
                <i class="ti ti-chart-line"></i>
                Laporan Laba
            </span>
            <h2>Laba</h2>
            <p>Pantau laba bersih dari penjualan, modal barang, margin, performa harian, kasir, dan barang penyumbang laba.</p>
        </div>

        <div class="laporan-hero-actions">
            <a href="{{ $exportUrl('/admin/laporan/export/laba') }}" class="laporan-btn laporan-btn-primary">
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
        <a href="{{ url('/admin/laporan/laba') }}" class="is-active"><i class="ti ti-chart-line"></i> Laba</a>
        <a href="{{ url('/admin/laporan/barang-terlaris') }}"><i class="ti ti-award"></i> Barang Terlaris</a>
        <a href="{{ url('/admin/laporan/restock') }}"><i class="ti ti-stack-push"></i> Restock</a>
    </section>

    <section class="laporan-filter-panel" data-aos="fade-up" data-aos-delay="150">
        <div>
            <span>Filter Periode</span>
            <h3>Atur Rentang Tanggal</h3>
        </div>

        <form action="{{ url('/admin/laporan/laba') }}" method="GET" class="laporan-filter-form">
            <label>
                <span>Tanggal Mulai</span>
                <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}">
            </label>
            <label>
                <span>Tanggal Selesai</span>
                <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai }}">
            </label>
            <button type="submit" class="laporan-btn laporan-btn-ghost"><i class="ti ti-filter"></i> Filter</button>
            <a href="{{ url('/admin/laporan/laba') }}" class="laporan-btn laporan-btn-muted"><i class="ti ti-refresh"></i> Reset</a>
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
                    <h3>Penjualan vs Laba Harian</h3>
                </div>
            </div>

            @if (empty($penjualanHarian))
                <div class="laporan-empty is-chart">
                    <span><i class="ti ti-chart-line"></i></span>
                    <h4>Belum ada data laba</h4>
                </div>
            @else
                <div class="laporan-chart-wrap"><canvas id="laporanSalesChart"></canvas></div>
            @endif
        </article>

        <article class="laporan-chart-card">
            <div class="laporan-card-head">
                <div>
                    <span>Barang</span>
                    <h3>Laba per Barang</h3>
                </div>
            </div>

            @if (empty($barangTerlaris))
                <div class="laporan-empty is-chart">
                    <span><i class="ti ti-package-off"></i></span>
                    <h4>Belum ada laba barang</h4>
                </div>
            @else
                <div class="laporan-chart-wrap chart-small"><canvas id="laporanTopProductChart"></canvas></div>
            @endif
        </article>

        <article class="laporan-panel">
            <div class="laporan-card-head">
                <div>
                    <span>Margin</span>
                    <h3>Komposisi Keuangan</h3>
                </div>
            </div>

            <div class="laporan-method-list">
                <div class="laporan-method-item method-cash">
                    <span><i class="ti ti-chart-line"></i></span>
                    <div>
                        <strong>Margin Laba</strong>
                        <small>Dari total penjualan</small>
                    </div>
                    <b>{{ number_format($marginLaba, 1, ',', '.') }}%</b>
                </div>
                <div class="laporan-method-item method-qris">
                    <span><i class="ti ti-cash"></i></span>
                    <div>
                        <strong>Penjualan</strong>
                        <small>Omzet kotor</small>
                    </div>
                    <b>{{ app_rupiah($totalPenjualan) }}</b>
                </div>
                <div class="laporan-method-item method-ewallet">
                    <span><i class="ti ti-wallet"></i></span>
                    <div>
                        <strong>Modal</strong>
                        <small>Harga beli barang</small>
                    </div>
                    <b>{{ app_rupiah($totalModal) }}</b>
                </div>
            </div>
        </article>
    </section>

    <section class="laporan-grid" data-aos="fade-up" data-aos-delay="300">
        <article class="laporan-panel">
            <div class="laporan-card-head">
                <div>
                    <span>Harian</span>
                    <h3>Laba Harian</h3>
                </div>
            </div>

            @if (empty($penjualanHarian))
                <div class="laporan-empty">
                    <span><i class="ti ti-calendar-off"></i></span>
                    <h4>Belum ada data laba</h4>
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
                                <th>Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($penjualanHarian as $row)
                                @php
                                    $rp = (float) ($row['total_penjualan'] ?? 0);
                                    $rm = (float) ($row['total_modal'] ?? 0);
                                    $rl = (float) ($row['total_laba'] ?? 0);
                                    $rmg = $rp > 0 ? ($rl / $rp) * 100 : 0;
                                @endphp
                                <tr>
                                    <td>
                                        <span class="laporan-date">
                                            <i class="ti ti-calendar"></i>
                                            {{ app_date($row['tanggal'] ?? '', 'd M Y') }}
                                        </span>
                                    </td>
                                    <td><strong class="laporan-number">{{ $row['total_transaksi'] ?? 0 }}</strong></td>
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
        </article>

        <article class="laporan-panel">
            <div class="laporan-card-head">
                <div>
                    <span>Kasir</span>
                    <h3>Laba Per Kasir</h3>
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
                                <th>Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($penjualanKasir as $idx => $row)
                                @php
                                    $kp = (float) ($row['total_penjualan'] ?? 0);
                                    $km = (float) ($row['total_modal'] ?? 0);
                                    $kl = (float) ($row['total_laba'] ?? 0);
                                    $kmg = $kp > 0 ? ($kl / $kp) * 100 : 0;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="laporan-product">
                                            <span>{{ $idx + 1 }}</span>
                                            <div>
                                                <strong>{{ $row['nama_kasir'] ?? '-' }}</strong>
                                                <small>Kontribusi laba</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong class="laporan-number">{{ $row['total_transaksi'] ?? 0 }}</strong></td>
                                    <td><strong class="laporan-money">{{ app_rupiah($kp) }}</strong></td>
                                    <td><strong class="laporan-money is-muted">{{ app_rupiah($km) }}</strong></td>
                                    <td>
                                        <strong class="laporan-money {{ $kl >= 0 ? 'is-profit' : 'is-loss' }}">
                                            {{ app_rupiah($kl) }}
                                        </strong>
                                    </td>
                                    <td>
                                        <strong class="laporan-number">{{ number_format($kmg, 1, ',', '.') }}%</strong>
                                    </td>
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
                    <h3>Laba Per Barang</h3>
                </div>
            </div>

            @if (empty($barangTerlaris))
                <div class="laporan-empty">
                    <span><i class="ti ti-package-off"></i></span>
                    <h4>Belum ada data barang</h4>
                </div>
            @else
                <div class="laporan-table-wrap">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Qty</th>
                                <th>Penjualan</th>
                                <th>Modal</th>
                                <th>Laba</th>
                                <th>Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($barangTerlaris as $idx => $row)
                                @php
                                    $bp = (float) ($row['total_penjualan'] ?? 0);
                                    $bm = (float) ($row['total_modal'] ?? 0);
                                    $bl = (float) ($row['total_laba'] ?? 0);
                                    $bmg = $bp > 0 ? ($bl / $bp) * 100 : 0;
                                @endphp
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
                                    <td><strong class="laporan-money">{{ app_rupiah($bp) }}</strong></td>
                                    <td><strong class="laporan-money is-muted">{{ app_rupiah($bm) }}</strong></td>
                                    <td>
                                        <strong class="laporan-money {{ $bl >= 0 ? 'is-profit' : 'is-loss' }}">
                                            {{ app_rupiah($bl) }}
                                        </strong>
                                    </td>
                                    <td>
                                        <strong class="laporan-number">{{ number_format($bmg, 1, ',', '.') }}%</strong>
                                    </td>
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
        'payments' => ['labels' => [], 'values' => []],
        'products' => $chartBarang,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection
