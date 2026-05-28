@php
    $pageCss = ['assets/css/kasir-dashboard.css'];
    $pageScripts = ['assets/js/kasir-dashboard.js'];

    $authUser = $user ?? $currentUser ?? [];
    $transaksiTerbaru = $transaksiTerbaru ?? [];

    $totalTransaksiHariIni = (int) ($totalTransaksiHariIni ?? 0);
    $totalPenjualanHariIni = (float) ($totalPenjualanHariIni ?? 0);
    $totalItemHariIni = (int) ($totalItemHariIni ?? 0);

    $username = (string) ($authUser['username'] ?? 'Kasir');
    $email = (string) ($authUser['email'] ?? '-');

    $methodLabel = fn ($m) => match (strtolower((string) $m)) {
        'cash' => 'Cash', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'ewallet' => 'E-Wallet',
        default => ucfirst((string) $m),
    };
    $methodIcon = fn ($m) => match (strtolower((string) $m)) {
        'cash' => 'ti ti-cash', 'qris' => 'ti ti-qrcode', 'transfer' => 'ti ti-building-bank',
        'ewallet' => 'ti ti-wallet', default => 'ti ti-credit-card',
    };

    $summaryCards = [
        ['class' => 'summary-green', 'icon' => 'ti ti-receipt', 'label' => 'Transaksi Hari Ini', 'count' => $totalTransaksiHariIni, 'format' => 'thousand', 'prefix' => '', 'desc' => 'Transaksi yang kamu proses'],
        ['class' => 'summary-blue', 'icon' => 'ti ti-cash', 'label' => 'Penjualan Hari Ini', 'count' => (int) $totalPenjualanHariIni, 'format' => 'rupiah', 'prefix' => 'Rp ', 'desc' => 'Total omzet transaksi'],
        ['class' => 'summary-orange', 'icon' => 'ti ti-packages', 'label' => 'Item Terjual', 'count' => $totalItemHariIni, 'format' => 'thousand', 'prefix' => '', 'desc' => 'Total item keluar'],
    ];
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Dashboard Kasir',
    'activeMenu' => $activeMenu ?? 'dashboard',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="kasir-dashboard-page">
    <section class="kasir-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="kasir-hero-content">
            <span class="kasir-eyebrow">
                <i class="ti ti-cash-register"></i>
                Dashboard Kasir
            </span>
            <h2>Halo, {{ $username }}</h2>
        </div>

        <div class="kasir-hero-actions">
            <a href="{{ url('/kasir/transaksi') }}" class="kasir-btn kasir-btn-primary">
                <i class="ti ti-shopping-cart-plus"></i>
                Mulai Transaksi
            </a>
            <a href="{{ url('/kasir/profil') }}" class="kasir-btn kasir-btn-soft">
                <i class="ti ti-user-circle"></i>
                Profil Saya
            </a>
        </div>
    </section>

    <section class="kasir-summary summary-count-3">
        @foreach ($summaryCards as $idx => $card)
            <article class="kasir-summary-card {{ $card['class'] }}" data-aos="zoom-in" data-aos-delay="{{ 80 + ($idx * 100) }}">
                <span class="kasir-summary-icon"><i class="{{ $card['icon'] }}"></i></span>
                <div>
                    <small>{{ $card['label'] }}</small>
                    <strong data-counter="{{ $card['count'] }}"
                            data-counter-format="{{ $card['format'] }}"
                            @if ($card['prefix'] !== '') data-counter-prefix="{{ $card['prefix'] }}" @endif>
                        {{ $card['prefix'] }}0
                    </strong>
                    <p>{{ $card['desc'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="kasir-layout">
        <article class="kasir-panel kasir-transaction-panel" data-aos="fade-right" data-aos-delay="200">
            <div class="kasir-panel-head">
                <div>
                    <span>Riwayat</span>
                    <h3>Transaksi Terbaru Saya</h3>
                </div>
                <a href="{{ url('/kasir/transaksi') }}" class="kasir-mini-link">
                    <i class="ti ti-plus"></i>
                    Transaksi Baru
                </a>
            </div>

            @if (empty($transaksiTerbaru))
                <div class="kasir-empty">
                    <span><i class="ti ti-receipt-off"></i></span>
                    <h4>Belum ada transaksi</h4>
                    <p>Mulai transaksi dari tombol di atas.</p>
                    <a href="{{ url('/kasir/transaksi') }}" class="kasir-btn kasir-btn-form">
                        <i class="ti ti-shopping-cart-plus"></i>
                        Mulai Transaksi
                    </a>
                </div>
            @else
                <div class="kasir-table-wrap">
                    <table class="kasir-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Tanggal</th>
                                <th>Metode</th>
                                <th>Total</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transaksiTerbaru as $t)
                                @php
                                    $id = (int) ($t['id'] ?? 0);
                                    $kode = (string) ($t['kode_transaksi'] ?? '-');
                                    $tgl = (string) ($t['tanggal'] ?? '');
                                    $method = strtolower((string) ($t['metode_bayar'] ?? '-'));
                                    $total = (float) ($t['total_jual'] ?? 0);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="kasir-code">
                                            <span><i class="ti ti-receipt"></i></span>
                                            <div>
                                                <strong>{{ $kode }}</strong>
                                                <small>ID: {{ $id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="kasir-date">
                                            <i class="ti ti-calendar"></i>
                                            {{ app_date($tgl) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="kasir-method method-{{ $method }}">
                                            <i class="{{ $methodIcon($method) }}"></i>
                                            {{ $methodLabel($method) }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="kasir-money">{{ app_rupiah($total) }}</strong>
                                    </td>
                                    <td>
                                        <div class="kasir-actions">
                                            <a href="{{ url('/kasir/transaksi/struk/'.$id) }}"
                                               class="kasir-action-btn action-struk"
                                               title="Lihat struk">
                                                <i class="ti ti-receipt"></i>
                                            </a>
                                            <a href="{{ url('/kasir/transaksi/pdf/'.$id) }}"
                                               class="kasir-action-btn action-pdf"
                                               title="Download PDF">
                                                <i class="ti ti-file-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>

        <aside class="kasir-side" data-aos="fade-left" data-aos-delay="200">
            <div class="kasir-profile-card">
                <span class="kasir-profile-icon"><i class="ti ti-user-circle"></i></span>
                <h4>{{ $username }}</h4>
                <p>{{ $email }}</p>

                <div class="kasir-profile-list">
                    <a href="{{ url('/kasir/transaksi') }}">
                        <i class="ti ti-shopping-cart-plus"></i>
                        <span>
                            <strong>POS Transaksi</strong>
                            <small>Buka halaman kasir</small>
                        </span>
                    </a>
                    <a href="{{ url('/kasir/profil') }}">
                        <i class="ti ti-user-cog"></i>
                        <span>
                            <strong>Profil Saya</strong>
                            <small>Kelola akun kasir</small>
                        </span>
                    </a>
                </div>
            </div>

            <div class="kasir-tips-card">
                <span><i class="ti ti-bulb"></i></span>
                <h4>Checklist Kasir</h4>
                <ul>
                    <li><i class="ti ti-check"></i> Pastikan barang dan qty sudah benar sebelum simpan.</li>
                    <li><i class="ti ti-check"></i> Untuk cash, cek nominal bayar dan kembalian.</li>
                    <li><i class="ti ti-check"></i> Cetak atau salin struk setelah transaksi selesai.</li>
                </ul>
            </div>
        </aside>
    </section>
</div>
@endsection
