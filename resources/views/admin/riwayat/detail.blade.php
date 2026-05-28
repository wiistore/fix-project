@php
    $pageCss = ['assets/css/riwayat.css'];

    $transaksi = $transaksi ?? [];
    $items = $items ?? ($detailTransaksi ?? []);
    $detailSummary = $detailSummary ?? [];

    $methodLabel = fn ($m) => match (strtolower((string) $m)) {
        'cash' => 'Cash', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'ewallet' => 'E-Wallet',
        default => ucfirst((string) $m),
    };
    $methodIcon = fn ($m) => match (strtolower((string) $m)) {
        'cash' => 'ti ti-cash', 'qris' => 'ti ti-qrcode', 'transfer' => 'ti ti-building-bank',
        'ewallet' => 'ti ti-wallet', default => 'ti ti-credit-card',
    };

    $id = (int) ($transaksi['id'] ?? 0);
    $kode = (string) ($transaksi['kode_transaksi'] ?? '-');
    $tanggal = (string) ($transaksi['tanggal'] ?? '');
    $kasir = (string) ($transaksi['nama_kasir'] ?? '-');
    $method = strtolower((string) ($transaksi['metode_bayar'] ?? '-'));

    $totalJual = (float) ($transaksi['total_jual'] ?? ($detailSummary['total_jual'] ?? 0));
    $totalBeli = (float) ($transaksi['total_beli'] ?? ($detailSummary['total_beli'] ?? 0));
    $totalLaba = (float) ($transaksi['total_laba'] ?? ($detailSummary['total_laba'] ?? 0));
    $nominalBayar = (float) ($transaksi['nominal_bayar'] ?? 0);
    $kembalian = (float) ($transaksi['kembalian'] ?? 0);

    $totalItem = (int) ($detailSummary['total_item'] ?? count($items));
    $totalQty = (int) ($detailSummary['total_qty'] ?? 0);

    if ($totalQty <= 0) {
        foreach ($items as $i) $totalQty += (int) ($i['qty'] ?? 0);
    }

    $labaPercent = $totalJual > 0 ? ($totalLaba / $totalJual) * 100 : 0;

    $summaryCards = [
        ['class' => 'summary-green', 'icon' => 'ti ti-cash', 'label' => 'Total Penjualan', 'value' => app_rupiah($totalJual), 'desc' => 'Nilai transaksi'],
        ['class' => 'summary-blue', 'icon' => 'ti ti-packages', 'label' => 'Total Qty', 'value' => $totalQty, 'desc' => $totalItem.' jenis item'],
        ['class' => 'summary-orange', 'icon' => 'ti ti-chart-line', 'label' => 'Total Laba', 'value' => app_rupiah($totalLaba), 'desc' => number_format($labaPercent, 1, ',', '.').'% margin'],
    ];
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Detail Transaksi',
    'activeMenu' => $activeMenu ?? 'riwayat',
    'pageCss' => $pageCss,
])

@section('content')
<div class="riwayat-page">
    <section class="riwayat-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="riwayat-hero-content">
            <span class="riwayat-eyebrow">
                <i class="ti ti-receipt"></i>
                Detail Transaksi
            </span>
            <h2>{{ $kode }}</h2>
            <p>Detail item, pembayaran, modal, dan laba transaksi.</p>
        </div>

        <div class="riwayat-hero-actions">
            <a href="{{ url('/admin/riwayat-transaksi') }}" class="riwayat-btn riwayat-btn-soft">
                <i class="ti ti-arrow-left"></i>
                Kembali
            </a>
            <a href="{{ url('/admin/transaksi/struk/'.$id) }}" class="riwayat-btn riwayat-btn-primary">
                <i class="ti ti-receipt"></i>
                Lihat Struk
            </a>
        </div>
    </section>

    <section class="riwayat-summary summary-count-3" data-aos="fade-up" data-aos-delay="140">
        @foreach ($summaryCards as $idx => $card)
            <article class="riwayat-summary-card {{ $card['class'] }}" data-aos="zoom-in" data-aos-delay="{{ 80 + ($idx * 100) }}">
                <span class="riwayat-summary-icon"><i class="{{ $card['icon'] }}"></i></span>
                <div>
                    <small>{{ $card['label'] }}</small>
                    <strong>{{ $card['value'] }}</strong>
                    <p>{{ $card['desc'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="riwayat-detail-layout" data-aos="fade-up" data-aos-delay="200">
        <article class="riwayat-detail-panel">
            <div class="riwayat-detail-head">
                <div>
                    <span>Informasi</span>
                    <h3>Informasi Transaksi</h3>
                </div>
                <span class="riwayat-detail-status">
                    <i class="ti ti-circle-check"></i>
                    Selesai
                </span>
            </div>

            <div class="riwayat-info-grid">
                <div class="riwayat-info-item">
                    <span>Kode Transaksi</span>
                    <strong>{{ $kode }}</strong>
                </div>
                <div class="riwayat-info-item">
                    <span>Tanggal</span>
                    <strong>{{ app_date($tanggal) }}</strong>
                </div>
                <div class="riwayat-info-item">
                    <span>Kasir</span>
                    <strong>{{ $kasir }}</strong>
                </div>
                <div class="riwayat-info-item">
                    <span>Metode Bayar</span>
                    <strong class="riwayat-method-text">
                        <i class="{{ $methodIcon($method) }}"></i>
                        {{ $methodLabel($method) }}
                    </strong>
                </div>
                <div class="riwayat-info-item">
                    <span>Nominal Bayar</span>
                    <strong>{{ app_rupiah($nominalBayar) }}</strong>
                </div>
                <div class="riwayat-info-item">
                    <span>Kembalian</span>
                    <strong class="is-change">{{ app_rupiah($kembalian) }}</strong>
                </div>
                <div class="riwayat-info-item">
                    <span>Total Modal</span>
                    <strong>{{ app_rupiah($totalBeli) }}</strong>
                </div>
                <div class="riwayat-info-item">
                    <span>Total Laba</span>
                    <strong class="{{ $totalLaba >= 0 ? 'is-profit' : 'is-loss' }}">
                        {{ app_rupiah($totalLaba) }}
                    </strong>
                </div>
            </div>
        </article>

        <aside class="riwayat-detail-side">
            <div class="riwayat-detail-action-card">
                <span class="riwayat-detail-action-icon"><i class="ti ti-bolt"></i></span>
                <h4>Aksi Cepat</h4>
                <p>Cek struk, download PDF, atau balik ke riwayat.</p>

                <div class="riwayat-detail-actions">
                    <a href="{{ url('/admin/transaksi/struk/'.$id) }}" class="riwayat-detail-action action-struk">
                        <i class="ti ti-receipt"></i>
                        <span>
                            <strong>Lihat Struk</strong>
                            <small>Preview dan cetak struk</small>
                        </span>
                    </a>
                    <a href="{{ url('/admin/transaksi/pdf/'.$id) }}" class="riwayat-detail-action action-pdf">
                        <i class="ti ti-file-download"></i>
                        <span>
                            <strong>Download PDF</strong>
                            <small>Struk thermal PDF</small>
                        </span>
                    </a>
                    <a href="{{ url('/admin/riwayat-transaksi') }}" class="riwayat-detail-action action-back">
                        <i class="ti ti-arrow-left"></i>
                        <span>
                            <strong>Kembali</strong>
                            <small>Ke daftar riwayat</small>
                        </span>
                    </a>
                </div>
            </div>
        </aside>
    </section>

    <section class="riwayat-panel" data-aos="fade-up" data-aos-delay="200">
        <div class="riwayat-panel-header">
            <div>
                <span>Item</span>
                <h3>Detail Item Transaksi</h3>
            </div>
            <div class="riwayat-detail-total">
                <span>Total Laba</span>
                <strong>{{ app_rupiah($totalLaba) }}</strong>
            </div>
        </div>

        @if (empty($items))
            <div class="riwayat-empty">
                <span><i class="ti ti-package-off"></i></span>
                <h4>Detail item kosong</h4>
                <p>Transaksi ada, tapi item-nya kosong.</p>
            </div>
        @else
            <div class="riwayat-table-wrap">
                <table class="riwayat-table riwayat-detail-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Barang</th>
                            <th>Barcode</th>
                            <th>Satuan</th>
                            <th>Qty</th>
                            <th>Harga Jual</th>
                            <th>Harga Beli</th>
                            <th>Subtotal Jual</th>
                            <th>Subtotal Beli</th>
                            <th>Laba Item</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($items as $idx => $item)
                            @php
                                $qty = (int) ($item['qty'] ?? 0);
                                $hj = (float) ($item['harga_jual'] ?? 0);
                                $hb = (float) ($item['harga_beli'] ?? 0);
                                $sj = (float) ($item['subtotal_jual'] ?? ($qty * $hj));
                                $sb = (float) ($item['subtotal_beli'] ?? ($qty * $hb));
                                $li = (float) ($item['laba_item'] ?? ($sj - $sb));
                            @endphp
                            <tr>
                                <td><span class="riwayat-number">{{ $idx + 1 }}</span></td>
                                <td>
                                    <div class="riwayat-code">
                                        <span class="riwayat-code-icon"><i class="ti ti-package"></i></span>
                                        <div>
                                            <strong>{{ $item['nama_barang'] ?? '-' }}</strong>
                                            <small>{{ $item['kode_barang'] ?? '-' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="riwayat-date">
                                        <i class="ti ti-barcode"></i>
                                        {{ ($item['barcode'] ?? '') !== '' ? $item['barcode'] : '-' }}
                                    </span>
                                </td>
                                <td>{{ $item['satuan'] ?? '-' }}</td>
                                <td>
                                    <span class="riwayat-qty">
                                        <i class="ti ti-x"></i>
                                        {{ $qty }}
                                    </span>
                                </td>
                                <td><strong class="riwayat-money">{{ app_rupiah($hj) }}</strong></td>
                                <td><strong class="riwayat-money is-muted">{{ app_rupiah($hb) }}</strong></td>
                                <td><strong class="riwayat-money">{{ app_rupiah($sj) }}</strong></td>
                                <td><strong class="riwayat-money is-muted">{{ app_rupiah($sb) }}</strong></td>
                                <td>
                                    <strong class="riwayat-money {{ $li >= 0 ? 'is-profit' : 'is-loss' }}">
                                        {{ app_rupiah($li) }}
                                    </strong>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
