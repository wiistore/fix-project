@php
    $pageCss = ['assets/css/kategori.css'];
    $pageScripts = ['assets/js/kategori.js'];

    $kategoris = $kategoris ?? [];
    $flash = $flash ?? [];
    $success = $flash['success'] ?? null;
    $error = $flash['error'] ?? null;

    $totalKategori = count($kategoris);
    $totalDenganDeskripsi = 0;
    $totalTanpaDeskripsi = 0;

    foreach ($kategoris as $k) {
        if (trim((string) ($k['deskripsi'] ?? '')) !== '') {
            $totalDenganDeskripsi++;
        } else {
            $totalTanpaDeskripsi++;
        }
    }
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Data Kategori',
    'activeMenu' => $activeMenu ?? 'kategori',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="kategori-page">
    @if ($success)
        <div class="kategori-alert kategori-alert-success">
            <i class="ti ti-circle-check"></i>
            <span>{{ $success }}</span>
        </div>
    @endif

    @if ($error)
        <div class="kategori-alert kategori-alert-error">
            <i class="ti ti-alert-triangle"></i>
            <span>{{ $error }}</span>
        </div>
    @endif

    <section class="kategori-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="kategori-hero-content">
            <span class="kategori-eyebrow">
                <i class="ti ti-folder"></i>
                Master Kategori
            </span>

            <h2>Data Kategori</h2>
        </div>

        <div class="kategori-hero-actions">
            <a href="{{ url('/admin/kategori/create') }}" class="kategori-btn kategori-btn-primary">
                <i class="ti ti-plus"></i>
                Tambah Kategori
            </a>

            <a href="{{ url('/admin/barang') }}" class="kategori-btn kategori-btn-soft">
                <i class="ti ti-package"></i>
                Lihat Barang
            </a>
        </div>
    </section>

    <section class="kategori-summary" data-aos="fade-up" data-aos-delay="140">
        <article class="kategori-summary-card summary-green" data-aos="zoom-in" data-aos-delay="80">
            <span class="kategori-summary-icon"><i class="ti ti-folders"></i></span>
            <div>
                <small>Total Kategori</small>
                <strong>{{ $totalKategori }}</strong>
                <p>Semua kategori</p>
            </div>
        </article>

        <article class="kategori-summary-card summary-blue" data-aos="zoom-in" data-aos-delay="180">
            <span class="kategori-summary-icon"><i class="ti ti-notes"></i></span>
            <div>
                <small>Punya Deskripsi</small>
                <strong>{{ $totalDenganDeskripsi }}</strong>
                <p>Data lebih jelas</p>
            </div>
        </article>

        <article class="kategori-summary-card summary-orange" data-aos="zoom-in" data-aos-delay="280">
            <span class="kategori-summary-icon"><i class="ti ti-note-off"></i></span>
            <div>
                <small>Tanpa Deskripsi</small>
                <strong>{{ $totalTanpaDeskripsi }}</strong>
                <p>Masih bisa dirapikan</p>
            </div>
        </article>
    </section>

    <section class="kategori-panel" data-aos="fade-up" data-aos-delay="200">
        <div class="kategori-panel-header">
            <div>
                <span>Inventori</span>
                <h3>Daftar Kategori</h3>
            </div>

            <div class="kategori-tools">
                <label class="kategori-search">
                    <i class="ti ti-search"></i>
                    <input type="search" placeholder="Cari nama atau deskripsi..." data-kategori-search>
                </label>

                <button type="button" class="kategori-btn kategori-btn-ghost" data-kategori-reset>
                    <i class="ti ti-refresh"></i>
                    Reset
                </button>
            </div>
        </div>

        @if (empty($kategoris))
            <div class="kategori-empty">
                <span><i class="ti ti-folder-off"></i></span>
                <h4>Belum ada kategori</h4>
                <p>Tambahkan kategori dulu supaya barang tidak numpuk di satu tempat seperti laci kabel bekas.</p>
                <a href="{{ url('/admin/kategori/create') }}" class="kategori-btn kategori-btn-primary">
                    <i class="ti ti-plus"></i>
                    Tambah Kategori
                </a>
            </div>
        @else
            <div class="kategori-table-wrap">
                <table class="kategori-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi</th>
                            <th>Dibuat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody data-kategori-table-body>
                        @foreach ($kategoris as $index => $k)
                            @php
                                $nama = (string) ($k['nama'] ?? '-');
                                $deskripsi = (string) ($k['deskripsi'] ?? '');
                                $createdAt = $k['created_at'] ?? '';
                                $searchText = strtolower(trim($nama.' '.$deskripsi.' '.$createdAt));
                            @endphp
                            <tr data-kategori-row data-search="{{ $searchText }}">
                                <td>
                                    <span class="kategori-number">{{ $index + 1 }}</span>
                                </td>

                                <td>
                                    <div class="kategori-name">
                                        <span class="kategori-name-icon"><i class="ti ti-folder"></i></span>
                                        <div>
                                            <strong>{{ $nama }}</strong>
                                            <small>ID: {{ $k['id'] ?? '-' }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="{{ trim($deskripsi) === '' ? 'kategori-desc is-empty' : 'kategori-desc' }}">
                                        {{ app_short_text($deskripsi) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="kategori-date">
                                        <i class="ti ti-calendar"></i>
                                        {{ app_date($createdAt) }}
                                    </span>
                                </td>

                                <td>
                                    <div class="kategori-actions">
                                        <a href="{{ url('/admin/kategori/edit/'.($k['id'] ?? '')) }}"
                                           class="kategori-action-btn action-edit"
                                           title="Edit kategori"
                                           aria-label="Edit kategori">
                                            <i class="ti ti-edit"></i>
                                        </a>

                                        <form action="{{ url('/admin/kategori/delete/'.($k['id'] ?? '')) }}"
                                              method="POST"
                                              data-kategori-delete-form
                                              data-confirm-title="Hapus Kategori"
                                              data-confirm-message="Kategori {{ $nama }} akan dihapus kalau belum dipakai barang. Lanjut?">
                                            @csrf
                                            <button type="submit"
                                                    class="kategori-action-btn action-delete"
                                                    title="Hapus kategori"
                                                    aria-label="Hapus kategori">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="kategori-filter-empty" data-kategori-filter-empty hidden>
                    <span><i class="ti ti-search-off"></i></span>
                    <h4>Data tidak ketemu</h4>
                    <p>Keyword-nya terlalu niat. Coba cari yang lebih masuk akal.</p>
                </div>
            </div>

            @include('components.pagination', ['pagination' => $pagination ?? null])
        @endif
    </section>
</div>
@endsection
