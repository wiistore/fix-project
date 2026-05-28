@php
    $pageCss = ['assets/css/pos.css'];
    $pageScripts = ['assets/js/pos.js'];

    $authUser = $user ?? $currentUser ?? null;
    $role = strtolower((string) ($authUser['role'] ?? 'kasir'));
    $isAdmin = $role === 'admin';

    $barangs = $barangs ?? [];
    $metodePembayaran = $metodePembayaran ?? [
        'cash' => 'Cash', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'ewallet' => 'E-Wallet',
    ];
    $flash = $flash ?? [];
    $success = $flash['success'] ?? null;
    $error = $flash['error'] ?? null;

    $storeUrl = '/transaksi/store';
    $dashboardUrl = $isAdmin ? '/admin/dashboard' : '/kasir/dashboard';
    $historyUrl = $isAdmin ? '/admin/riwayat-transaksi' : '/kasir/dashboard';
    $barangUrl = $isAdmin ? '/admin/barang' : null;

    $stockMeta = function (int $stok, int $min) {
        if ($stok <= 0) return ['label' => 'Habis', 'class' => 'stock-empty', 'icon' => 'ti ti-alert-circle'];
        if ($stok <= $min) return ['label' => 'Menipis', 'class' => 'stock-low', 'icon' => 'ti ti-alert-triangle'];
        return ['label' => 'Aman', 'class' => 'stock-safe', 'icon' => 'ti ti-circle-check'];
    };

    $kategoriMap = [];
    foreach ($barangs as $b) {
        $kid = (string) ($b['id_kategori'] ?? '');
        $kn = (string) ($b['nama_kategori'] ?? 'Lainnya');
        if ($kid !== '') $kategoriMap[$kid] = $kn;
    }

    $totalBarangAktif = count($barangs);
    $totalStok = array_sum(array_map(fn ($b) => (int) ($b['stok'] ?? 0), $barangs));
    $totalBarangTersedia = count(array_filter($barangs, fn ($b) => (int) ($b['stok'] ?? 0) > 0));

    $productsForJs = array_map(fn ($b) => [
        'id' => (int) ($b['id'] ?? 0),
        'kode_barang' => (string) ($b['kode_barang'] ?? ''),
        'barcode' => (string) ($b['barcode'] ?? ''),
        'nama' => (string) ($b['nama'] ?? ''),
        'kategori_id' => (string) ($b['id_kategori'] ?? ''),
        'kategori' => (string) ($b['nama_kategori'] ?? ''),
        'satuan' => (string) ($b['satuan'] ?? ''),
        'harga_jual' => (float) ($b['harga_jual'] ?? 0),
        'stok' => (int) ($b['stok'] ?? 0),
        'stok_minimum' => (int) ($b['stok_minimum'] ?? 0),
    ], $barangs);
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Transaksi POS',
    'activeMenu' => 'transaksi',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="pos-page" data-pos-page>
    @if ($success)
        <div class="pos-alert pos-alert-success">
            <i class="ti ti-circle-check"></i>
            <span>{{ $success }}</span>
        </div>
    @endif

    @if ($error)
        <div class="pos-alert pos-alert-error">
            <i class="ti ti-alert-triangle"></i>
            <span>{{ $error }}</span>
        </div>
    @endif

    <section class="pos-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="pos-hero-content">
            <span class="pos-eyebrow">
                <i class="ti ti-shopping-cart"></i>
                Point of Sale
            </span>
            <h2>Transaksi Penjualan</h2>
        </div>

        <div class="pos-hero-actions">
            <a href="{{ url($dashboardUrl) }}" class="pos-btn pos-btn-soft">
                <i class="ti ti-arrow-left"></i>
                Dashboard
            </a>

            @if ($isAdmin)
                <a href="{{ url($historyUrl) }}" class="pos-btn pos-btn-soft">
                    <i class="ti ti-history"></i>
                    Riwayat
                </a>
                <a href="{{ url($barangUrl) }}" class="pos-btn pos-btn-primary">
                    <i class="ti ti-package"></i>
                    Barang
                </a>
            @endif
        </div>
    </section>

    <section class="pos-summary summary-count-3">
        <article class="pos-summary-card summary-green" data-aos="zoom-in" data-aos-delay="80">
            <span class="pos-summary-icon"><i class="ti ti-package"></i></span>
            <div>
                <small>Barang Aktif</small>
                <strong data-counter="{{ $totalBarangAktif }}" data-counter-format="thousand">0</strong>
                <p>Semua barang aktif</p>
            </div>
        </article>

        <article class="pos-summary-card summary-blue" data-aos="zoom-in" data-aos-delay="180">
            <span class="pos-summary-icon"><i class="ti ti-packages"></i></span>
            <div>
                <small>Total Stok</small>
                <strong data-counter="{{ $totalStok }}" data-counter-format="thousand">0</strong>
                <p>Akumulasi stok barang</p>
            </div>
        </article>

        <article class="pos-summary-card summary-orange" data-aos="zoom-in" data-aos-delay="280">
            <span class="pos-summary-icon"><i class="ti ti-shopping-bag-check"></i></span>
            <div>
                <small>Siap Dijual</small>
                <strong data-counter="{{ $totalBarangTersedia }}" data-counter-format="thousand">0</strong>
                <p>Stok lebih dari nol</p>
            </div>
        </article>
    </section>

    <section class="pos-layout">
        <article class="pos-products-panel" data-aos="fade-right" data-aos-delay="200">
            <div class="pos-panel-head">
                <div>
                    <span>Produk</span>
                    <h3>Pilih Barang</h3>
                </div>
                <button type="button" class="pos-scan-btn" data-pos-focus-search>
                    <i class="ti ti-barcode"></i>
                    Scan / F2
                </button>
            </div>

            <div class="pos-toolbar">
                <label class="pos-search">
                    <i class="ti ti-search"></i>
                    <input type="search" placeholder="Cari nama barang, kode, atau scan barcode..." autocomplete="off" data-pos-search>
                    <kbd>F2</kbd>
                </label>

                <div class="pos-category-tabs" data-pos-categories>
                    <button type="button" class="is-active" data-category="all">Semua</button>
                    @foreach ($kategoriMap as $kid => $kn)
                        <button type="button" data-category="{{ $kid }}">{{ $kn }}</button>
                    @endforeach
                </div>
            </div>

            @if (empty($barangs))
                <div class="pos-empty">
                    <span><i class="ti ti-package-off"></i></span>
                    <h4>Belum ada barang aktif</h4>
                    <p>Tambahkan barang dan restock dulu.</p>
                    @if ($isAdmin)
                        <a href="{{ url('/admin/barang/create') }}" class="pos-btn pos-btn-form">
                            <i class="ti ti-plus"></i> Tambah Barang
                        </a>
                    @endif
                </div>
            @else
                <div class="pos-product-grid" data-product-list>
                    @foreach ($barangs as $b)
                        @php
                            $id = (int) ($b['id'] ?? 0);
                            $kode = (string) ($b['kode_barang'] ?? '-');
                            $barcode = (string) ($b['barcode'] ?? '');
                            $namaB = (string) ($b['nama'] ?? '-');
                            $kid = (string) ($b['id_kategori'] ?? '');
                            $kn = (string) ($b['nama_kategori'] ?? 'Lainnya');
                            $satuan = (string) ($b['satuan'] ?? 'pcs');
                            $harga = (float) ($b['harga_jual'] ?? 0);
                            $stok = (int) ($b['stok'] ?? 0);
                            $smin = (int) ($b['stok_minimum'] ?? 0);
                            $meta = $stockMeta($stok, $smin);
                            $isOut = $stok <= 0;
                            $searchText = strtolower(implode(' ', [$kode, $barcode, $namaB, $kn, $satuan]));
                        @endphp

                        <button type="button"
                                class="pos-product-card {{ $isOut ? 'is-disabled' : '' }}"
                                data-product-card
                                data-product-id="{{ $id }}"
                                data-category="{{ $kid }}"
                                data-search="{{ $searchText }}"
                                {{ $isOut ? 'disabled' : '' }}>
                            <span class="pos-product-icon"><i class="ti ti-package"></i></span>
                            <span class="pos-product-info">
                                <strong>{{ $namaB }}</strong>
                                <small>
                                    {{ $kode }}{{ $barcode !== '' ? ' • '.$barcode : '' }}
                                </small>
                            </span>
                            <span class="pos-product-bottom">
                                <span class="pos-product-price">{{ app_rupiah($harga) }}</span>
                                <span class="pos-product-stock {{ $meta['class'] }}">
                                    <i class="{{ $meta['icon'] }}"></i>
                                    {{ $meta['label'] }} · {{ $stok }} {{ $satuan }}
                                </span>
                            </span>
                            @if ($isOut)
                                <span class="pos-product-disabled-label">Habis</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div class="pos-filter-empty" data-product-empty hidden>
                    <span><i class="ti ti-search-off"></i></span>
                    <h4>Barang tidak ketemu</h4>
                    <p>Coba cari nama, kode, atau barcode yang bener.</p>
                </div>
            @endif
        </article>

        <aside class="pos-cart-panel" data-aos="fade-left" data-aos-delay="250">
            <form action="{{ url($storeUrl) }}" method="POST" class="pos-form" data-pos-form>
                @csrf
                <input type="hidden" name="cart_json" value="" data-cart-json>

                <div class="pos-cart-head">
                    <div>
                        <span>Keranjang</span>
                        <h3>Transaksi Saat Ini</h3>
                        <p><strong data-cart-count>0</strong> item dipilih</p>
                    </div>
                    <button type="button" class="pos-clear-btn" data-cart-clear>
                        <i class="ti ti-trash"></i>
                        Kosongkan
                    </button>
                </div>

                <div class="pos-cart-items" data-cart-items>
                    <div class="pos-cart-empty" data-cart-empty>
                        <span><i class="ti ti-shopping-cart-off"></i></span>
                        <h4>Keranjang kosong</h4>
                        <p>Pilih barang dari daftar produk.</p>
                    </div>
                </div>

                <div class="pos-payment-box">
                    <div class="pos-total-box">
                        <span>Total Bayar</span>
                        <strong data-total-pay>Rp 0</strong>
                    </div>

                    <div class="pos-mini-summary">
                        <div>
                            <span>Total Item</span>
                            <strong data-total-item>0</strong>
                        </div>
                        <div>
                            <span>Kembalian</span>
                            <strong data-change>Rp 0</strong>
                        </div>
                    </div>

                    <div class="pos-payment-section">
                        <label>Metode Pembayaran</label>
                        <div class="pos-payment-methods">
                            @foreach ($metodePembayaran as $key => $label)
                                @php
                                    $icon = match ($key) {
                                        'cash' => 'ti ti-cash',
                                        'qris' => 'ti ti-qrcode',
                                        'transfer' => 'ti ti-building-bank',
                                        'ewallet' => 'ti ti-wallet',
                                        default => 'ti ti-credit-card',
                                    };
                                @endphp
                                <label class="pos-payment-method {{ $key === 'cash' ? 'is-active' : '' }}">
                                    <input type="radio" name="metode_pembayaran"
                                           value="{{ $key }}"
                                           {{ $key === 'cash' ? 'checked' : '' }}
                                           data-payment-method>
                                    <span>
                                        <i class="{{ $icon }}"></i>
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="pos-payment-section" data-cash-box>
                        <label for="nominalBayar">Nominal Bayar</label>
                        <div class="pos-money-input">
                            <span>Rp</span>
                            <input type="number" id="nominalBayar" name="nominal_bayar"
                                   value="" placeholder="0" min="0" step="1" data-cash-input>
                        </div>
                        <small>Wajib diisi untuk pembayaran cash.</small>
                    </div>

                    <div class="pos-warning" data-payment-warning hidden>
                        <i class="ti ti-alert-triangle"></i>
                        <span>Nominal bayar kurang dari total transaksi.</span>
                    </div>

                    <button type="submit" class="pos-pay-btn" data-pay-button disabled>
                        <i class="ti ti-device-floppy"></i>
                        Simpan Transaksi
                        <span>F9</span>
                    </button>

                    <p class="pos-payment-note">Harga dan stok final tetap dihitung ulang oleh backend.</p>
                </div>
            </form>
        </aside>
    </section>
</div>

<script type="application/json" id="posProductData">
    {!! json_encode($productsForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection
