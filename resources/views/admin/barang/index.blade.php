@php
    $pageCss = ['assets/css/barang.css'];
    $pageScripts = ['assets/js/barang.js'];
    $useBarcode = true;

    $barangs = $barangs ?? [];
    $summary = $summary ?? [];
    $flash = $flash ?? [];
    $success = $flash['success'] ?? null;
    $error = $flash['error'] ?? null;

    $stockMeta = function (int $stok, int $stokMin) {
        if ($stok <= 0) {
            return ['label' => 'Habis', 'class' => 'stock-empty', 'filter' => 'habis', 'icon' => 'ti ti-alert-circle'];
        }
        if ($stok <= $stokMin) {
            return ['label' => 'Menipis', 'class' => 'stock-low', 'filter' => 'menipis', 'icon' => 'ti ti-alert-triangle'];
        }
        return ['label' => 'Aman', 'class' => 'stock-safe', 'filter' => 'aman', 'icon' => 'ti ti-circle-check'];
    };
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Data Barang',
    'activeMenu' => $activeMenu ?? 'barang',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
    'useBarcode' => $useBarcode,
])

@section('content')
<div class="barang-page">
    @if ($success)
        <div class="barang-alert barang-alert-success">
            <i class="ti ti-circle-check"></i>
            <span>{{ $success }}</span>
        </div>
    @endif

    @if ($error)
        <div class="barang-alert barang-alert-error">
            <i class="ti ti-alert-triangle"></i>
            <span>{{ $error }}</span>
        </div>
    @endif

    <section class="barang-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="barang-hero-content">
            <span class="barang-eyebrow">
                <i class="ti ti-package"></i>
                Master Barang
            </span>
            <h2>Data Barang</h2>
        </div>

        <div class="barang-hero-actions">
            <a href="{{ url('/admin/barang/create') }}" class="barang-btn barang-btn-primary">
                <i class="ti ti-plus"></i>
                Tambah Barang
            </a>
            <a href="{{ url('/admin/restock') }}" class="barang-btn barang-btn-soft">
                <i class="ti ti-stack-push"></i>
                Restock
            </a>
        </div>
    </section>

    <section class="barang-summary" data-aos="fade-up" data-aos-delay="140">
        <article class="barang-summary-card summary-green" data-aos="zoom-in" data-aos-delay="80">
            <span class="barang-summary-icon"><i class="ti ti-package"></i></span>
            <div>
                <small>Total Barang</small>
                <strong>{{ $summary['total_barang'] ?? count($barangs) }}</strong>
                <p>Semua data barang</p>
            </div>
        </article>

        <article class="barang-summary-card summary-blue" data-aos="zoom-in" data-aos-delay="180">
            <span class="barang-summary-icon"><i class="ti ti-circle-check"></i></span>
            <div>
                <small>Barang Aktif</small>
                <strong>{{ $summary['barang_aktif'] ?? 0 }}</strong>
                <p>Siap transaksi</p>
            </div>
        </article>

        <article class="barang-summary-card summary-gray" data-aos="zoom-in" data-aos-delay="280">
            <span class="barang-summary-icon"><i class="ti ti-circle-off"></i></span>
            <div>
                <small>Nonaktif</small>
                <strong>{{ $summary['barang_nonaktif'] ?? 0 }}</strong>
                <p>Tidak dipakai</p>
            </div>
        </article>

        <article class="barang-summary-card summary-red" data-aos="zoom-in" data-aos-delay="380">
            <span class="barang-summary-icon"><i class="ti ti-alert-triangle"></i></span>
            <div>
                <small>Stok Menipis</small>
                <strong>{{ $summary['stok_menipis'] ?? 0 }}</strong>
                <p>Perlu dicek</p>
            </div>
        </article>
    </section>

    <section class="barang-panel" data-aos="fade-up" data-aos-delay="200">
        <div class="barang-panel-header">
            <div>
                <span>Inventori</span>
                <h3>Daftar Barang</h3>
            </div>

            <div class="barang-tools">
                <label class="barang-search">
                    <i class="ti ti-search"></i>
                    <input type="search" placeholder="Cari kode, barcode, nama, kategori..." data-barang-search>
                </label>

                <select class="barang-filter" data-barang-status-filter aria-label="Filter status">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>

                <select class="barang-filter" data-barang-stock-filter aria-label="Filter stok">
                    <option value="">Semua Stok</option>
                    <option value="aman">Aman</option>
                    <option value="menipis">Menipis</option>
                    <option value="habis">Habis</option>
                </select>

                <button type="button" class="barang-btn barang-btn-ghost" data-barang-reset>
                    <i class="ti ti-refresh"></i>
                    Reset
                </button>
            </div>
        </div>

        @if (empty($barangs))
            <div class="barang-empty">
                <span><i class="ti ti-package-off"></i></span>
                <h4>Belum ada data barang</h4>
                <p>Tambahkan barang pertama supaya koperasi siap transaksi.</p>
                <a href="{{ url('/admin/barang/create') }}" class="barang-btn barang-btn-primary">
                    <i class="ti ti-plus"></i> Tambah Barang
                </a>
            </div>
        @else
            <form action="{{ url('/admin/barang/label-bulk') }}"
                  method="POST"
                  data-barang-bulk-label-form
                  target="_blank">
                @csrf

                <div class="barang-bulk-bar" data-barang-bulk-bar>
                    <div class="barang-bulk-info">
                        <i class="ti ti-checks"></i>
                        <span><strong data-barang-bulk-count>0</strong> barang dipilih</span>
                    </div>

                    <button type="submit"
                            class="barang-btn barang-btn-primary"
                            data-barang-bulk-label-btn
                            disabled>
                        <i class="ti ti-printer"></i>
                        Cetak Label Terpilih
                    </button>
                </div>

                <div class="barang-table-wrap">
                    <table class="barang-table">
                        <thead>
                            <tr>
                                <th class="barang-col-check">
                                    <input type="checkbox" class="barang-checkbox" data-barang-select-all aria-label="Pilih semua">
                                </th>
                                <th>No</th>
                                <th>Barang</th>
                                <th>Barcode</th>
                                <th>Kategori</th>
                                <th>Satuan</th>
                                <th>Harga Jual</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>

                        <tbody data-barang-table-body>
                            @foreach ($barangs as $index => $item)
                                @php
                                    $status = strtolower((string) ($item['status'] ?? 'nonaktif'));
                                    $stok = (int) ($item['stok'] ?? 0);
                                    $stokMin = (int) ($item['stok_minimum'] ?? 0);
                                    $meta = $stockMeta($stok, $stokMin);
                                    $itemId = (string) ($item['id'] ?? '');
                                    $itemBarcode = (string) ($item['barcode'] ?? '');
                                    $hasBarcode = trim($itemBarcode) !== '';

                                    $searchText = strtolower(implode(' ', [
                                        $item['kode_barang'] ?? '',
                                        $item['barcode'] ?? '',
                                        $item['nama'] ?? '',
                                        $item['nama_kategori'] ?? '',
                                        $item['satuan'] ?? '',
                                        $status,
                                        $meta['label'],
                                    ]));
                                @endphp

                                <tr data-barang-row
                                    data-search="{{ $searchText }}"
                                    data-status="{{ $status }}"
                                    data-stock="{{ $meta['filter'] }}">
                                    <td class="barang-col-check">
                                        <input type="checkbox"
                                               class="barang-checkbox"
                                               name="ids[]"
                                               value="{{ $itemId }}"
                                               data-barang-checkbox
                                               {{ $hasBarcode ? '' : 'disabled' }}
                                               aria-label="Pilih barang {{ $item['nama'] ?? '' }}"
                                               title="{{ $hasBarcode ? 'Pilih untuk cetak label' : 'Belum punya barcode' }}">
                                    </td>

                                    <td>
                                        <span class="barang-number">{{ $index + 1 }}</span>
                                    </td>

                                    <td>
                                        <div class="barang-product">
                                            <span class="barang-product-icon"><i class="ti ti-package"></i></span>
                                            <div>
                                                <strong>{{ $item['nama'] ?? '-' }}</strong>
                                                <small>{{ $item['kode_barang'] ?? '-' }}</small>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="barang-code">
                                            <i class="ti ti-barcode"></i>
                                            {{ $hasBarcode ? $itemBarcode : '-' }}
                                        </span>
                                    </td>

                                    <td>{{ $item['nama_kategori'] ?? '-' }}</td>

                                    <td>
                                        <span class="barang-unit">{{ $item['satuan'] ?? '-' }}</span>
                                    </td>

                                    <td>
                                        <strong class="barang-price">
                                            {{ app_rupiah($item['harga_jual'] ?? 0) }}
                                        </strong>
                                    </td>

                                    <td>
                                        <div class="barang-stock {{ $meta['class'] }}">
                                            <span>
                                                <i class="{{ $meta['icon'] }}"></i>
                                                {{ $stok }}
                                            </span>
                                            <small>Min. {{ $stokMin }}</small>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="barang-status {{ $status === 'aktif' ? 'status-active' : 'status-inactive' }}">
                                            <i class="{{ $status === 'aktif' ? 'ti ti-circle-check' : 'ti ti-circle-off' }}"></i>
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="barang-actions">
                                            @if ($hasBarcode)
                                                <a href="{{ url('/admin/barang/label/'.$itemId) }}"
                                                   class="barang-action-btn action-print"
                                                   title="Cetak label barcode"
                                                   target="_blank">
                                                    <i class="ti ti-printer"></i>
                                                </a>
                                            @endif

                                            <a href="{{ url('/admin/barang/edit/'.$itemId) }}"
                                               class="barang-action-btn action-edit"
                                               title="Edit barang">
                                                <i class="ti ti-edit"></i>
                                            </a>

                                            <button type="button"
                                                    class="barang-action-btn action-delete"
                                                    title="Hapus barang"
                                                    data-barang-delete-trigger
                                                    data-delete-url="{{ url('/admin/barang/delete/'.$itemId) }}"
                                                    data-confirm-title="Hapus / Nonaktifkan Barang"
                                                    data-confirm-message="Barang {{ $item['nama'] ?? '-' }} akan dihapus kalau belum punya histori. Lanjut?">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="barang-filter-empty" data-barang-filter-empty hidden>
                        <span><i class="ti ti-search-off"></i></span>
                        <h4>Data tidak ketemu</h4>
                        <p>Filter atau keyword terlalu semangat. Longgarkan dikit.</p>
                    </div>
                </div>
            </form>

            <div data-barang-delete-forms hidden>
                @foreach ($barangs as $item)
                    <form action="{{ url('/admin/barang/delete/'.($item['id'] ?? '')) }}"
                          method="POST"
                          data-barang-delete-form
                          data-delete-id="{{ $item['id'] ?? '' }}">
                        @csrf
                    </form>
                @endforeach
            </div>

            @include('components.pagination', ['pagination' => $pagination ?? null])
        @endif
    </section>
</div>
@endsection
