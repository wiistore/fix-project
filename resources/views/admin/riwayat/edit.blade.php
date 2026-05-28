@php
    $pageCss = ['assets/css/riwayat.css'];

    $transaksi = $transaksi ?? [];
    $items = $items ?? [];
    $barangs = $barangs ?? [];
    $flash = $flash ?? [];

    $success = $flash['success'] ?? null;
    $error = $flash['error'] ?? null;

    $transaksiId = (int) ($transaksi['id'] ?? 0);
    $kodeTransaksi = (string) ($transaksi['kode_transaksi'] ?? '-');
    $metodeBayar = (string) ($transaksi['metode_bayar'] ?? 'cash');
    $nominalBayar = (float) ($transaksi['nominal_bayar'] ?? 0);
    $isCash = $metodeBayar === 'cash';
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Edit Transaksi',
    'activeMenu' => $activeMenu ?? 'riwayat',
    'pageCss' => $pageCss,
])

@section('content')
<div class="riwayat-page">
    @if ($error)
        <div class="riwayat-alert riwayat-alert-error">
            <i class="ti ti-alert-triangle"></i>
            <span>{{ $error }}</span>
        </div>
    @endif

    <section class="riwayat-hero riwayat-edit-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="riwayat-hero-content">
            <span class="riwayat-eyebrow">
                <i class="ti ti-pencil"></i>
                Edit Transaksi
            </span>
            <h2>Edit: {{ $kodeTransaksi }}</h2>
        </div>

        <div class="riwayat-hero-actions">
            <a href="{{ url('/admin/riwayat-transaksi') }}" class="riwayat-btn riwayat-btn-soft">
                <i class="ti ti-arrow-left"></i>
                Kembali
            </a>
        </div>
    </section>

    <section class="riwayat-edit-layout" data-aos="fade-up" data-aos-delay="150">
        <form id="editTransaksiForm"
              action="{{ url('/admin/riwayat-transaksi/update/'.$transaksiId) }}"
              method="POST">
            @csrf
            <input type="hidden" name="cart_json" id="cartJsonInput" value="">

            <div class="riwayat-edit-grid">
                <div class="riwayat-edit-products">
                    <div class="riwayat-edit-card">
                        <div class="riwayat-edit-card-head">
                            <h3><i class="ti ti-package"></i> Pilih Barang</h3>
                            <label class="riwayat-edit-search">
                                <i class="ti ti-search"></i>
                                <input type="text" placeholder="Cari barang..." id="editSearchBarang">
                            </label>
                        </div>

                        <div class="riwayat-edit-product-list" id="editProductList">
                            @foreach ($barangs as $b)
                                @php
                                    $bId = (int) ($b['id'] ?? 0);
                                    $bNama = (string) ($b['nama'] ?? '-');
                                    $bKode = (string) ($b['kode_barang'] ?? '-');
                                    $bBarcode = (string) ($b['barcode'] ?? '');
                                    $bHarga = (float) ($b['harga_jual'] ?? 0);
                                    $bStok = (int) ($b['stok'] ?? 0);
                                    $bSatuan = (string) ($b['satuan'] ?? 'pcs');
                                @endphp
                                <div class="riwayat-edit-product-item {{ $bStok <= 0 ? 'is-empty' : '' }}"
                                     data-product-id="{{ $bId }}"
                                     data-product-name="{{ $bNama }}"
                                     data-product-code="{{ $bKode }}"
                                     data-product-barcode="{{ $bBarcode }}"
                                     data-product-price="{{ $bHarga }}"
                                     data-product-stock="{{ $bStok }}"
                                     data-product-unit="{{ $bSatuan }}"
                                     data-search-text="{{ strtolower($bNama.' '.$bKode.' '.$bBarcode) }}">
                                    <div class="riwayat-edit-product-info">
                                        <strong>{{ $bNama }}</strong>
                                        <small>{{ $bKode }} • {{ app_rupiah($bHarga) }}</small>
                                    </div>
                                    <div class="riwayat-edit-product-meta">
                                        <span class="riwayat-edit-stock">Stok: {{ $bStok }} {{ $bSatuan }}</span>
                                        <button type="button"
                                                class="riwayat-edit-add-btn"
                                                data-add-product="{{ $bId }}"
                                                {{ $bStok <= 0 ? 'disabled' : '' }}>
                                            <i class="ti ti-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="riwayat-edit-cart">
                    <div class="riwayat-edit-card">
                        <div class="riwayat-edit-card-head">
                            <h3><i class="ti ti-shopping-cart"></i> Keranjang</h3>
                            <span class="riwayat-edit-badge" id="editCartCount">0 item</span>
                        </div>

                        <div class="riwayat-edit-cart-items" id="editCartItems">
                            <div class="riwayat-edit-cart-empty" id="editCartEmpty">
                                <i class="ti ti-shopping-cart-off"></i>
                                <p>Keranjang kosong</p>
                            </div>
                        </div>

                        <div class="riwayat-edit-cart-summary">
                            <div class="riwayat-edit-summary-row">
                                <span>Total</span>
                                <strong id="editCartTotal">Rp 0</strong>
                            </div>

                            <div class="riwayat-edit-summary-row is-info">
                                <label for="editMetodeBayar">Metode Bayar</label>
                                <select id="editMetodeBayar" name="metode_bayar" class="riwayat-edit-nominal-input" style="width:140px;">
                                    <option value="cash" {{ $metodeBayar === 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="qris" {{ $metodeBayar === 'qris' ? 'selected' : '' }}>QRIS</option>
                                    <option value="transfer" {{ $metodeBayar === 'transfer' ? 'selected' : '' }}>Transfer</option>
                                    <option value="ewallet" {{ $metodeBayar === 'ewallet' ? 'selected' : '' }}>E-Wallet</option>
                                </select>
                            </div>

                            <div id="editCashSection" style="{{ $isCash ? '' : 'display:none;' }}">
                                <div class="riwayat-edit-summary-row">
                                    <label for="editNominalBayar">Nominal Bayar</label>
                                    <input type="number" id="editNominalBayar" name="nominal_bayar"
                                           value="{{ (int) $nominalBayar }}" min="0" step="1"
                                           class="riwayat-edit-nominal-input">
                                </div>

                                <div class="riwayat-edit-summary-row">
                                    <span>Kembalian</span>
                                    <strong id="editCartKembalian">Rp 0</strong>
                                </div>
                            </div>
                        </div>

                        <div class="riwayat-edit-cart-actions">
                            <button type="submit" class="riwayat-btn riwayat-btn-primary riwayat-btn-full" id="editSubmitBtn">
                                <i class="ti ti-device-floppy"></i>
                                Simpan Perubahan
                            </button>
                            <a href="{{ url('/admin/riwayat-transaksi') }}" class="riwayat-btn riwayat-btn-ghost riwayat-btn-full">
                                <i class="ti ti-x"></i>
                                Batal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var allProducts = {!! json_encode(array_map(fn ($b) => [
        'id' => (int) ($b['id'] ?? 0),
        'nama' => (string) ($b['nama'] ?? '-'),
        'kode_barang' => (string) ($b['kode_barang'] ?? '-'),
        'harga_jual' => (float) ($b['harga_jual'] ?? 0),
        'stok' => (int) ($b['stok'] ?? 0),
        'satuan' => (string) ($b['satuan'] ?? 'pcs'),
    ], $barangs), JSON_UNESCAPED_UNICODE) !!};

    // Convert array to map by id
    var productsMap = {};
    allProducts.forEach(function(p) { productsMap[p.id] = p; });

    var oldItems = {!! json_encode(array_map(fn ($i) => [
        'id_barang' => (int) ($i['id_barang'] ?? 0),
        'qty' => (int) ($i['qty'] ?? 0),
        'nama' => (string) ($i['nama_barang'] ?? '-'),
    ], $items), JSON_UNESCAPED_UNICODE) !!};

    // Tambahkan stok lama (akan di-rollback saat update)
    oldItems.forEach(function(it) {
        if (productsMap[it.id_barang]) {
            productsMap[it.id_barang].stok += it.qty;
        }
    });

    var cart = {};
    oldItems.forEach(function(it) {
        cart[it.id_barang] = { id_barang: it.id_barang, qty: it.qty };
    });

    var isCash = {{ $isCash ? 'true' : 'false' }};
    var metodeBayarSelect = document.getElementById('editMetodeBayar');
    var cashSection = document.getElementById('editCashSection');
    var cartItemsEl = document.getElementById('editCartItems');
    var cartEmptyEl = document.getElementById('editCartEmpty');
    var cartCountEl = document.getElementById('editCartCount');
    var cartTotalEl = document.getElementById('editCartTotal');
    var cartJsonInput = document.getElementById('cartJsonInput');
    var submitBtn = document.getElementById('editSubmitBtn');
    var nominalInput = document.getElementById('editNominalBayar');
    var kembalianEl = document.getElementById('editCartKembalian');
    var searchInput = document.getElementById('editSearchBarang');

    function formatMoney(val) { return 'Rp ' + Math.round(val).toLocaleString('id-ID'); }

    function getAvailableStock(idBarang) {
        var p = productsMap[idBarang];
        if (!p) return 0;
        var inCart = cart[idBarang] ? cart[idBarang].qty : 0;
        return p.stok - inCart;
    }

    function renderCart() {
        var keys = Object.keys(cart);
        var totalItems = 0, totalHarga = 0, html = '';

        keys.forEach(function(idBarang) {
            idBarang = parseInt(idBarang);
            var item = cart[idBarang];
            if (!item || item.qty <= 0) return;
            var p = productsMap[idBarang];
            if (!p) return;
            totalItems += item.qty;
            var subtotal = p.harga_jual * item.qty;
            totalHarga += subtotal;

            html += '<div class="riwayat-edit-cart-item" data-cart-item="' + idBarang + '">';
            html += '<div class="riwayat-edit-cart-item-info"><strong>' + escapeHtml(p.nama) + '</strong>';
            html += '<small>' + formatMoney(p.harga_jual) + ' / ' + escapeHtml(p.satuan) + '</small></div>';
            html += '<div class="riwayat-edit-cart-item-controls">';
            html += '<button type="button" class="riwayat-edit-qty-btn" data-action="decrease" data-id="' + idBarang + '"><i class="ti ti-minus"></i></button>';
            html += '<input type="number" class="riwayat-edit-qty-input" value="' + item.qty + '" min="0" max="' + p.stok + '" data-qty-input="' + idBarang + '">';
            html += '<button type="button" class="riwayat-edit-qty-btn" data-action="increase" data-id="' + idBarang + '" ' + (getAvailableStock(idBarang) <= 0 ? 'disabled' : '') + '><i class="ti ti-plus"></i></button>';
            html += '<button type="button" class="riwayat-edit-remove-btn" data-action="remove" data-id="' + idBarang + '"><i class="ti ti-trash"></i></button>';
            html += '</div>';
            html += '<div class="riwayat-edit-cart-item-subtotal"><span>' + formatMoney(subtotal) + '</span></div>';
            html += '</div>';
        });

        if (html === '') {
            cartEmptyEl.style.display = '';
            cartItemsEl.innerHTML = '';
            cartItemsEl.appendChild(cartEmptyEl);
        } else {
            cartEmptyEl.style.display = 'none';
            cartItemsEl.innerHTML = html;
        }

        cartCountEl.textContent = totalItems + ' item';
        cartTotalEl.textContent = formatMoney(totalHarga);

        if (isCash && nominalInput && kembalianEl) {
            var nominal = parseFloat(nominalInput.value) || 0;
            kembalianEl.textContent = formatMoney(Math.max(0, nominal - totalHarga));
        }

        var cartData = { items: [] };
        keys.forEach(function(idBarang) {
            idBarang = parseInt(idBarang);
            var it = cart[idBarang];
            if (it && it.qty > 0) cartData.items.push({ id_barang: idBarang, qty: it.qty });
        });
        cartJsonInput.value = JSON.stringify(cartData);
        submitBtn.disabled = cartData.items.length === 0;
    }

    function addToCart(idBarang, qty) {
        idBarang = parseInt(idBarang);
        qty = parseInt(qty) || 1;
        if (!productsMap[idBarang]) return;
        if (!cart[idBarang]) cart[idBarang] = { id_barang: idBarang, qty: 0 };
        var available = getAvailableStock(idBarang);
        if (available <= 0) return;
        cart[idBarang].qty += Math.min(qty, available);
        renderCart();
    }

    function setCartQty(idBarang, qty) {
        idBarang = parseInt(idBarang);
        qty = parseInt(qty) || 0;
        if (qty <= 0) {
            delete cart[idBarang];
        } else {
            var p = productsMap[idBarang];
            if (!p) return;
            cart[idBarang] = { id_barang: idBarang, qty: Math.min(qty, p.stok) };
        }
        renderCart();
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    document.querySelectorAll('[data-add-product]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            addToCart(parseInt(this.getAttribute('data-add-product')), 1);
        });
    });

    cartItemsEl.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        var action = btn.getAttribute('data-action');
        var id = parseInt(btn.getAttribute('data-id'));
        if (action === 'increase') addToCart(id, 1);
        else if (action === 'decrease') {
            if (cart[id]) {
                cart[id].qty--;
                if (cart[id].qty <= 0) delete cart[id];
                renderCart();
            }
        } else if (action === 'remove') {
            delete cart[id];
            renderCart();
        }
    });

    cartItemsEl.addEventListener('change', function(e) {
        var input = e.target.closest('[data-qty-input]');
        if (!input) return;
        setCartQty(parseInt(input.getAttribute('data-qty-input')), parseInt(input.value) || 0);
    });

    if (nominalInput) nominalInput.addEventListener('input', renderCart);

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var keyword = this.value.toLowerCase().trim();
            document.querySelectorAll('[data-search-text]').forEach(function(el) {
                var t = el.getAttribute('data-search-text');
                el.style.display = keyword === '' || t.includes(keyword) ? '' : 'none';
            });
        });
    }

    if (metodeBayarSelect) {
        metodeBayarSelect.addEventListener('change', function() {
            isCash = this.value === 'cash';
            cashSection.style.display = isCash ? '' : 'none';
            renderCart();
        });
    }

    renderCart();
});
</script>

<style>
.riwayat-edit-layout { margin-top: 20px; }
.riwayat-edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.riwayat-edit-card { background: var(--card-bg, #fff); border-radius: 12px; border: 1px solid var(--border-color, #e5e7eb); padding: 20px; }
.riwayat-edit-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; gap: 12px; }
.riwayat-edit-card-head h3 { font-size: 1rem; display: flex; align-items: center; gap: 8px; margin: 0; }
.riwayat-edit-search { display: flex; align-items: center; gap: 6px; background: var(--input-bg, #f9fafb); border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; padding: 6px 10px; }
.riwayat-edit-search input { border: none; outline: none; background: none; font-size: 0.85rem; width: 160px; }
.riwayat-edit-badge { font-size: 0.75rem; background: var(--primary-light, #ecfdf5); color: var(--primary, #10b981); padding: 4px 10px; border-radius: 20px; font-weight: 600; }
.riwayat-edit-product-list { max-height: 500px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }
.riwayat-edit-product-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; }
.riwayat-edit-product-item:hover { background: var(--hover-bg, #f9fafb); }
.riwayat-edit-product-item.is-empty { opacity: 0.5; }
.riwayat-edit-product-info strong { display: block; font-size: 0.85rem; }
.riwayat-edit-product-info small { color: var(--text-muted, #6b7280); font-size: 0.75rem; }
.riwayat-edit-product-meta { display: flex; align-items: center; gap: 10px; }
.riwayat-edit-stock { font-size: 0.75rem; color: var(--text-muted, #6b7280); }
.riwayat-edit-add-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid var(--primary, #10b981); color: var(--primary, #10b981); background: none; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.riwayat-edit-add-btn:hover:not(:disabled) { background: var(--primary, #10b981); color: #fff; }
.riwayat-edit-add-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.riwayat-edit-cart-items { min-height: 200px; max-height: 400px; overflow-y: auto; }
.riwayat-edit-cart-empty { text-align: center; padding: 40px 20px; color: var(--text-muted, #6b7280); }
.riwayat-edit-cart-empty i { font-size: 2rem; margin-bottom: 8px; display: block; }
.riwayat-edit-cart-item { display: grid; grid-template-columns: 1fr auto; gap: 8px; padding: 10px 0; border-bottom: 1px solid var(--border-color, #e5e7eb); align-items: center; }
.riwayat-edit-cart-item-info strong { font-size: 0.85rem; display: block; }
.riwayat-edit-cart-item-info small { color: var(--text-muted, #6b7280); font-size: 0.75rem; }
.riwayat-edit-cart-item-controls { display: flex; align-items: center; gap: 4px; }
.riwayat-edit-qty-btn { width: 26px; height: 26px; border-radius: 6px; border: 1px solid var(--border-color, #e5e7eb); background: none; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.riwayat-edit-qty-btn:hover:not(:disabled) { background: var(--hover-bg, #f3f4f6); }
.riwayat-edit-qty-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.riwayat-edit-qty-input { width: 48px; text-align: center; border: 1px solid var(--border-color, #e5e7eb); border-radius: 6px; padding: 4px; font-size: 0.85rem; }
.riwayat-edit-remove-btn { width: 26px; height: 26px; border-radius: 6px; border: 1px solid #ef4444; color: #ef4444; background: none; cursor: pointer; display: flex; align-items: center; justify-content: center; margin-left: 4px; }
.riwayat-edit-remove-btn:hover { background: #ef4444; color: #fff; }
.riwayat-edit-cart-item-subtotal { grid-column: 1 / -1; text-align: right; font-size: 0.8rem; color: var(--text-muted, #6b7280); }
.riwayat-edit-cart-summary { border-top: 2px solid var(--border-color, #e5e7eb); padding-top: 16px; margin-top: 16px; display: flex; flex-direction: column; gap: 10px; }
.riwayat-edit-summary-row { display: flex; align-items: center; justify-content: space-between; }
.riwayat-edit-summary-row strong { font-size: 1rem; }
.riwayat-edit-summary-row.is-info { font-size: 0.85rem; color: var(--text-muted, #6b7280); }
.riwayat-edit-nominal-input { width: 140px; border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; padding: 8px 12px; font-size: 0.9rem; text-align: right; }
.riwayat-edit-cart-actions { margin-top: 16px; display: flex; flex-direction: column; gap: 8px; }
.riwayat-btn-full { width: 100%; justify-content: center; }
@media (max-width: 768px) { .riwayat-edit-grid { grid-template-columns: 1fr; } }
</style>
@endsection
