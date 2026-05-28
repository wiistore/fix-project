@php
    $pageCss = ['assets/css/riwayat.css'];
    $pageScripts = ['assets/js/riwayat.js'];

    $transaksis = $transaksis ?? [];
    $summary = $summary ?? [];
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
    $statusBadge = fn ($s) => match ($s) {
        'selesai' => '<span class="riwayat-status-badge status-selesai"><i class="ti ti-circle-check"></i> Selesai</span>',
        'diubah' => '<span class="riwayat-status-badge status-diubah"><i class="ti ti-pencil"></i> Diubah</span>',
        'dibatalkan' => '<span class="riwayat-status-badge status-dibatalkan"><i class="ti ti-x"></i> Dibatalkan</span>',
        default => '<span class="riwayat-status-badge status-selesai"><i class="ti ti-circle-check"></i> Selesai</span>',
    };

    $totalTransaksi = (int) ($summary['total_transaksi'] ?? count($transaksis));
    $totalPenjualan = (float) ($summary['total_penjualan'] ?? 0);
    $totalLaba = (float) ($summary['total_laba'] ?? 0);

    $methodCounts = ['cash' => 0, 'qris' => 0, 'transfer' => 0, 'ewallet' => 0];
    foreach ($transaksis as $t) {
        if (($t['status'] ?? 'selesai') === 'dibatalkan') continue;
        $m = strtolower((string) ($t['metode_bayar'] ?? ''));
        if (! isset($methodCounts[$m])) $methodCounts[$m] = 0;
        $methodCounts[$m]++;
    }
    arsort($methodCounts);
    $topMethod = array_key_first($methodCounts);
    $topMethodCount = $topMethod !== null ? (int) $methodCounts[$topMethod] : 0;

    $summaryCards = [
        ['class' => 'summary-green', 'icon' => 'ti ti-receipt', 'label' => 'Total Transaksi', 'value' => $totalTransaksi, 'desc' => 'Transaksi valid'],
        ['class' => 'summary-blue', 'icon' => 'ti ti-cash', 'label' => 'Total Penjualan', 'value' => app_rupiah($totalPenjualan), 'desc' => 'Omzet transaksi'],
        ['class' => 'summary-orange', 'icon' => 'ti ti-chart-line', 'label' => 'Total Laba', 'value' => app_rupiah($totalLaba), 'desc' => 'Laba dari transaksi'],
        ['class' => 'summary-purple', 'icon' => $methodIcon($topMethod), 'label' => 'Metode Terbanyak', 'value' => $topMethodCount > 0 ? $methodLabel($topMethod) : '-', 'desc' => $topMethodCount > 0 ? $topMethodCount.' transaksi' : 'Belum ada data'],
    ];
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Riwayat Transaksi',
    'activeMenu' => $activeMenu ?? 'riwayat',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="riwayat-page">
    @if ($success)
        <div class="riwayat-alert riwayat-alert-success">
            <i class="ti ti-circle-check"></i>
            <span>{{ $success }}</span>
        </div>
    @endif

    @if ($error)
        <div class="riwayat-alert riwayat-alert-error">
            <i class="ti ti-alert-triangle"></i>
            <span>{{ $error }}</span>
        </div>
    @endif

    <section class="riwayat-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="riwayat-hero-content">
            <span class="riwayat-eyebrow">
                <i class="ti ti-history"></i>
                Riwayat Penjualan
            </span>
            <h2>Riwayat Transaksi</h2>
        </div>

        <div class="riwayat-hero-actions">
            <a href="{{ url('/admin/transaksi') }}" class="riwayat-btn riwayat-btn-primary">
                <i class="ti ti-shopping-cart-plus"></i>
                Transaksi Baru
            </a>
            <a href="{{ url('/admin/laporan') }}" class="riwayat-btn riwayat-btn-soft">
                <i class="ti ti-chart-bar"></i>
                Laporan
            </a>
        </div>
    </section>

    <section class="riwayat-summary summary-count-{{ count($summaryCards) }}" data-aos="fade-up" data-aos-delay="140">
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

    <section class="riwayat-panel" data-aos="fade-up" data-aos-delay="200">
        <div class="riwayat-panel-header">
            <div>
                <span>Transaksi</span>
                <h3>Daftar Riwayat</h3>
            </div>

            <div class="riwayat-tools">
                <form action="{{ url('/admin/riwayat-transaksi') }}" method="GET" class="riwayat-date-filter">
                    <label>
                        <span>Tanggal Mulai</span>
                        <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}">
                    </label>
                    <label>
                        <span>Tanggal Selesai</span>
                        <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai }}">
                    </label>
                    <button type="submit" class="riwayat-btn riwayat-btn-ghost">
                        <i class="ti ti-filter"></i> Filter
                    </button>
                    <a href="{{ url('/admin/riwayat-transaksi') }}" class="riwayat-btn riwayat-btn-muted">
                        <i class="ti ti-refresh"></i> Reset
                    </a>
                </form>

                <div class="riwayat-table-tools">
                    <label class="riwayat-search">
                        <i class="ti ti-search"></i>
                        <input type="search" placeholder="Cari kode, kasir, metode..." data-riwayat-search>
                    </label>
                    <select class="riwayat-filter" data-riwayat-method-filter aria-label="Filter metode">
                        <option value="">Semua Metode</option>
                        <option value="cash">Cash</option>
                        <option value="qris">QRIS</option>
                        <option value="transfer">Transfer</option>
                        <option value="ewallet">E-Wallet</option>
                    </select>
                </div>
            </div>
        </div>

        @if (empty($transaksis))
            <div class="riwayat-empty">
                <span><i class="ti ti-receipt-off"></i></span>
                <h4>Belum ada transaksi</h4>
                <p>Belum ada riwayat sesuai filter ini.</p>
                <a href="{{ url('/admin/transaksi') }}" class="riwayat-btn riwayat-btn-form">
                    <i class="ti ti-shopping-cart-plus"></i> Buat Transaksi
                </a>
            </div>
        @else
            <div class="riwayat-table-wrap">
                <table class="riwayat-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Transaksi</th>
                            <th>Tanggal</th>
                            <th>Kasir</th>
                            <th>Metode</th>
                            <th>Total</th>
                            <th>Laba</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody data-riwayat-table-body>
                        @foreach ($transaksis as $index => $t)
                            @php
                                $id = (int) ($t['id'] ?? 0);
                                $kode = (string) ($t['kode_transaksi'] ?? '-');
                                $tgl = (string) ($t['tanggal'] ?? '');
                                $kasir = (string) ($t['nama_kasir'] ?? '-');
                                $method = strtolower((string) ($t['metode_bayar'] ?? '-'));
                                $statusRow = (string) ($t['status'] ?? 'selesai');
                                $tjr = (float) ($t['total_jual'] ?? 0);
                                $tlr = (float) ($t['total_laba'] ?? 0);
                                $isCancel = $statusRow === 'dibatalkan';
                                $searchText = strtolower(implode(' ', [$kode, $tgl, $kasir, $method, $methodLabel($method), $statusRow]));
                            @endphp

                            <tr data-riwayat-row
                                data-search="{{ $searchText }}"
                                data-method="{{ $method }}"
                                class="{{ $isCancel ? 'row-dibatalkan' : '' }}">
                                <td><span class="riwayat-number">{{ $index + 1 }}</span></td>
                                <td>
                                    <div class="riwayat-code">
                                        <span class="riwayat-code-icon"><i class="ti ti-receipt"></i></span>
                                        <div>
                                            <strong>{{ $kode }}</strong>
                                            <small>ID: {{ $id }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="riwayat-date">
                                        <i class="ti ti-calendar"></i>
                                        {{ app_date($tgl) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="riwayat-user">
                                        <i class="ti ti-user"></i>
                                        {{ $kasir }}
                                    </span>
                                </td>
                                <td>
                                    <span class="riwayat-method method-{{ $method }}">
                                        <i class="{{ $methodIcon($method) }}"></i>
                                        {{ $methodLabel($method) }}
                                    </span>
                                </td>
                                <td>
                                    <strong class="riwayat-money {{ $isCancel ? 'is-muted' : '' }}">
                                        {{ app_rupiah($tjr) }}
                                    </strong>
                                </td>
                                <td>
                                    <strong class="riwayat-money {{ $isCancel ? 'is-muted' : ($tlr >= 0 ? 'is-profit' : 'is-loss') }}">
                                        {{ app_rupiah($tlr) }}
                                    </strong>
                                </td>
                                <td>{!! $statusBadge($statusRow) !!}</td>
                                <td>
                                    <div class="riwayat-actions">
                                        <a href="{{ url('/admin/riwayat-transaksi/detail/'.$id) }}"
                                           class="riwayat-action-btn action-detail" title="Lihat detail">
                                            <i class="ti ti-eye"></i>
                                        </a>

                                        @if (! $isCancel)
                                            <a href="{{ url('/admin/riwayat-transaksi/edit/'.$id) }}"
                                               class="riwayat-action-btn action-edit" title="Edit transaksi">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                            <button type="button"
                                                    class="riwayat-action-btn action-cancel"
                                                    title="Batalkan transaksi"
                                                    data-cancel-btn
                                                    data-cancel-id="{{ $id }}"
                                                    data-cancel-kode="{{ $kode }}">
                                                <i class="ti ti-x"></i>
                                            </button>
                                        @endif

                                        <a href="{{ url('/admin/transaksi/struk/'.$id) }}"
                                           class="riwayat-action-btn action-struk" title="Lihat struk">
                                            <i class="ti ti-receipt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="riwayat-filter-empty" data-riwayat-filter-empty hidden>
                    <span><i class="ti ti-search-off"></i></span>
                    <h4>Data tidak ketemu</h4>
                    <p>Keyword atau filter terlalu spesifik.</p>
                </div>
            </div>

            @include('components.pagination', ['pagination' => $pagination ?? null])
        @endif
    </section>
</div>

<style>
.riwayat-modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(2px); }
.riwayat-modal-overlay[hidden] { display: none; }
.riwayat-modal { background: #ffffff; border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: modalSlideIn 0.25s ease; }
@keyframes modalSlideIn { from { opacity: 0; transform: translateY(-20px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
.riwayat-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px 16px; border-bottom: 1px solid #f1f5f9; }
.riwayat-modal-header h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }
.riwayat-modal-header h3 i { color: #f59e0b; font-size: 1.3rem; }
.riwayat-modal-close { width: 32px; height: 32px; border: none; background: #f1f5f9; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b; }
.riwayat-modal-close:hover { background: #e2e8f0; color: #0f172a; }
.riwayat-modal-body { padding: 20px 24px; }
.riwayat-modal-body p { margin: 0 0 12px; color: #334155; font-size: 0.95rem; line-height: 1.5; }
.riwayat-modal-warning { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 10px; padding: 12px 14px; font-size: 0.85rem; color: #92400e; display: flex; align-items: flex-start; gap: 8px; margin-bottom: 16px !important; }
.riwayat-modal-warning i { color: #d97706; font-size: 1.1rem; margin-top: 1px; }
.riwayat-modal-field { margin-top: 4px; }
.riwayat-modal-field label { display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 6px; }
.riwayat-modal-field label span { color: #ef4444; }
.riwayat-modal-field textarea { width: 100%; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; font-size: 0.9rem; resize: vertical; font-family: inherit; }
.riwayat-modal-field textarea:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
.riwayat-modal-footer { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 16px 24px 20px; border-top: 1px solid #f1f5f9; }
.riwayat-modal-footer .riwayat-btn-danger { background: #ef4444; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.riwayat-modal-footer .riwayat-btn-danger:hover { background: #dc2626; }
.riwayat-modal-footer .riwayat-btn-ghost { background: none; border: 1px solid #e2e8f0; color: #64748b; padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; }
.riwayat-modal-footer .riwayat-btn-ghost:hover { background: #f8fafc; color: #334155; }
</style>

<div class="riwayat-modal-overlay" id="cancelModal" hidden>
    <div class="riwayat-modal">
        <div class="riwayat-modal-header">
            <h3><i class="ti ti-alert-triangle"></i> Batalkan Transaksi</h3>
            <button type="button" class="riwayat-modal-close" data-cancel-modal-close>
                <i class="ti ti-x"></i>
            </button>
        </div>

        <form id="cancelForm" method="POST" action="">
            @csrf
            <div class="riwayat-modal-body">
                <p>Anda yakin ingin membatalkan transaksi <strong id="cancelKode"></strong>?</p>
                <p class="riwayat-modal-warning">
                    <i class="ti ti-info-circle"></i>
                    Stok barang akan dikembalikan. Transaksi tidak akan masuk laporan.
                </p>

                <div class="riwayat-modal-field">
                    <label for="alasan_batal">Alasan Pembatalan <span>*</span></label>
                    <textarea id="alasan_batal" name="alasan_batal" rows="3"
                              placeholder="Tulis alasan pembatalan (wajib)" required></textarea>
                </div>
            </div>

            <div class="riwayat-modal-footer">
                <button type="submit" class="riwayat-btn riwayat-btn-danger">
                    <i class="ti ti-x"></i>
                    Batalkan Transaksi
                </button>
                <button type="button" class="riwayat-btn riwayat-btn-ghost" data-cancel-modal-close>Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('cancelModal');
    var form = document.getElementById('cancelForm');
    var kodeEl = document.getElementById('cancelKode');
    var closeBtns = document.querySelectorAll('[data-cancel-modal-close]');
    var cancelBtns = document.querySelectorAll('[data-cancel-btn]');
    var basePath = "{{ url('/admin/riwayat-transaksi/cancel/') }}/";

    cancelBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-cancel-id');
            var kode = this.getAttribute('data-cancel-kode');
            form.action = basePath + id;
            kodeEl.textContent = kode;
            modal.hidden = false;
        });
    });

    closeBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            modal.hidden = true;
            form.action = '';
            kodeEl.textContent = '';
            document.getElementById('alasan_batal').value = '';
        });
    });

    modal.addEventListener('click', function(e) {
        if (e.target === modal) modal.hidden = true;
    });
});
</script>
@endsection
