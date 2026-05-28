@php
    $pageCss = ['assets/css/struk.css'];
    $pageScripts = ['assets/js/struk.js'];

    $transaksi = $transaksi ?? [];
    $items = $items ?? ($detailTransaksi ?? []);
    $authUser = $user ?? $currentUser ?? [];

    $role = strtolower((string) ($authUser['role'] ?? 'kasir'));
    $isAdmin = $role === 'admin';

    $transaksiId = (int) ($transaksi['id'] ?? 0);
    $kodeTransaksi = (string) ($transaksi['kode_transaksi'] ?? '-');

    $backUrl = $isAdmin ? '/admin/transaksi' : '/kasir/transaksi';
    $dashboardUrl = $isAdmin ? '/admin/dashboard' : '/kasir/dashboard';
    $pdfUrl = $isAdmin
        ? '/admin/transaksi/pdf/'.$transaksiId
        : '/kasir/transaksi/pdf/'.$transaksiId;

    $totalQty = 0;
    foreach ($items as $row) {
        $totalQty += (int) ($row['qty'] ?? 0);
    }

    $receiptLines = [];
    $receiptLines[] = 'LAB KEWIRAUSAHAAN';
    $receiptLines[] = 'MTSN 8 BANYUWANGI';
    $receiptLines[] = '------------------------------';
    $receiptLines[] = 'No      : '.$kodeTransaksi;
    $receiptLines[] = 'Tanggal : '.app_date($transaksi['tanggal'] ?? $transaksi['created_at'] ?? '', 'd/m/Y H:i');
    $receiptLines[] = 'Kasir   : '.($transaksi['nama_kasir'] ?? app_user_name($authUser));
    $receiptLines[] = 'Metode  : '.strtoupper((string) ($transaksi['metode_bayar'] ?? '-'));
    $receiptLines[] = '------------------------------';

    foreach ($items as $item) {
        $namaB = (string) ($item['nama_barang'] ?? $item['nama'] ?? '-');
        $qty = (int) ($item['qty'] ?? 0);
        $harga = app_rupiah($item['harga_jual'] ?? 0);
        $sub = app_rupiah($item['subtotal_jual'] ?? 0);
        $receiptLines[] = $namaB;
        $receiptLines[] = $qty.' x '.$harga.' = '.$sub;
    }

    $receiptLines[] = '------------------------------';
    $receiptLines[] = 'Total Item : '.$totalQty;
    $receiptLines[] = 'Total      : '.app_rupiah($transaksi['total_jual'] ?? 0);
    $receiptLines[] = 'Bayar      : '.app_rupiah($transaksi['nominal_bayar'] ?? 0);
    $receiptLines[] = 'Kembalian  : '.app_rupiah($transaksi['kembalian'] ?? 0);
    $receiptLines[] = '------------------------------';
    $receiptLines[] = 'Terima kasih sudah berbelanja.';
    $receiptLines[] = 'Barang yang sudah dibeli harap dicek kembali.';

    $receiptText = implode("\n", $receiptLines);
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Struk Transaksi',
    'activeMenu' => 'transaksi',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="struk-page">
    <section class="struk-hero">
        <div class="struk-hero-content">
            <span class="struk-eyebrow">
                <i class="ti ti-receipt"></i>
                Transaksi Berhasil
            </span>
            <h2>Struk Transaksi</h2>
        </div>

        <div class="struk-hero-actions">
            <a href="{{ url($backUrl) }}" class="struk-btn struk-btn-primary">
                <i class="ti ti-plus"></i>
                Transaksi Baru
            </a>
            <a href="{{ url($dashboardUrl) }}" class="struk-btn struk-btn-soft">
                <i class="ti ti-layout-dashboard"></i>
                Dashboard
            </a>
        </div>
    </section>

    <section class="struk-layout">
        <article class="struk-preview-card">
            <div class="struk-card-head">
                <div>
                    <span>Preview Struk</span>
                    <h3>{{ $kodeTransaksi }}</h3>
                </div>
                <span class="struk-status-badge">
                    <i class="ti ti-circle-check"></i>
                    Sukses
                </span>
            </div>

            <div class="struk-copy-status" data-copy-status hidden>
                <i class="ti ti-check"></i>
                <span>Struk berhasil disalin.</span>
            </div>

            <div class="struk-receipt-wrap">
                @include('shared.struk-content', [
                    'transaksi' => $transaksi,
                    'items' => $items,
                    'detailTransaksi' => $items,
                    'user' => $authUser,
                ])
            </div>
        </article>

        <aside class="struk-side">
            <div class="struk-action-card">
                <span class="struk-action-icon"><i class="ti ti-printer"></i></span>
                <h4>Aksi Struk</h4>
                <p>Pilih metode keluaran struk: cetak fisik, PDF, atau salin teks.</p>

                <div class="struk-action-list">
                    <button type="button" class="struk-action-btn action-print" data-print-receipt>
                        <i class="ti ti-printer"></i>
                        <span>
                            <strong>Cetak Fisik</strong>
                            <small>Print area struk 80mm</small>
                        </span>
                    </button>

                    <a href="{{ url($pdfUrl) }}" class="struk-action-btn action-pdf">
                        <i class="ti ti-file-download"></i>
                        <span>
                            <strong>Download PDF</strong>
                            <small>Struk thermal PDF</small>
                        </span>
                    </a>

                    <button type="button" class="struk-action-btn action-copy" data-copy-receipt>
                        <i class="ti ti-copy"></i>
                        <span>
                            <strong>Salin Struk</strong>
                            <small>Untuk WhatsApp/chat</small>
                        </span>
                    </button>
                </div>
            </div>

            <div class="struk-summary-card">
                <h4>Ringkasan</h4>

                <div class="struk-summary-row">
                    <span>Total Item</span>
                    <strong>{{ $totalQty }}</strong>
                </div>
                <div class="struk-summary-row">
                    <span>Total Bayar</span>
                    <strong>{{ app_rupiah($transaksi['total_jual'] ?? 0) }}</strong>
                </div>
                <div class="struk-summary-row">
                    <span>Nominal Bayar</span>
                    <strong>{{ app_rupiah($transaksi['nominal_bayar'] ?? 0) }}</strong>
                </div>
                <div class="struk-summary-row is-green">
                    <span>Kembalian</span>
                    <strong>{{ app_rupiah($transaksi['kembalian'] ?? 0) }}</strong>
                </div>
            </div>
        </aside>
    </section>
</div>

<script type="application/json" id="strukReceiptText">
    {!! json_encode($receiptText, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection
