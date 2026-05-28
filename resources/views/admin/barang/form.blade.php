@php
    $pageCss = ['assets/css/barang.css'];
    $pageScripts = ['assets/js/barang.js'];
    $useBarcode = true;

    $formAction = $formAction ?? '/admin/barang/store';
    $formMode = $formMode ?? 'create';
    $barang = $barang ?? null;
    $kategoris = $kategoris ?? [];
    $errors = $errors ?? [];
    $old = $old ?? [];

    $kodeBarang = $old['kode_barang'] ?? ($barang['kode_barang'] ?? '');
    $barcode = $old['barcode'] ?? ($barang['barcode'] ?? '');
    $nama = $old['nama'] ?? ($barang['nama'] ?? '');
    $idKategori = $old['id_kategori'] ?? ($barang['id_kategori'] ?? '');
    $satuan = $old['satuan'] ?? ($barang['satuan'] ?? 'pcs');
    $hargaJual = $old['harga_jual'] ?? ($barang['harga_jual'] ?? '');
    $stokMinimum = $old['stok_minimum'] ?? ($barang['stok_minimum'] ?? 5);
    $status = $old['status'] ?? ($barang['status'] ?? 'aktif');

    $isEdit = $formMode === 'edit';
    $fieldErr = fn ($f) => isset($errors[$f]) ? ' is-invalid' : '';
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Form Barang',
    'activeMenu' => $activeMenu ?? 'barang',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
    'useBarcode' => $useBarcode,
])

@section('content')
<div class="barang-page">
    <section class="barang-hero barang-form-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="barang-hero-content">
            <span class="barang-eyebrow">
                <i class="{{ $isEdit ? 'ti ti-edit' : 'ti ti-package-plus' }}"></i>
                {{ $isEdit ? 'Edit Master Barang' : 'Tambah Master Barang' }}
            </span>
            <h2>{{ $title }}</h2>
            <p>
                {{ $isEdit
                    ? 'Perbarui data barang. Stok dikunci, ubah lewat menu Restock atau Transaksi.'
                    : 'Tambah barang baru. Barcode boleh dikosongkan, akan auto-generate.' }}
            </p>
        </div>

        <div class="barang-hero-actions">
            <a href="{{ url('/admin/barang') }}" class="barang-btn barang-btn-soft">
                <i class="ti ti-arrow-left"></i>
                Kembali
            </a>
        </div>
    </section>

    <section class="barang-form-layout" data-aos="fade-up" data-aos-delay="150">
        <article class="barang-form-card">
            <div class="barang-form-head">
                <div>
                    <span>Form Barang</span>
                    <h3>{{ $isEdit ? 'Edit Data Barang' : 'Tambah Barang Baru' }}</h3>
                </div>

                <span class="barang-form-badge">
                    <i class="{{ $isEdit ? 'ti ti-pencil' : 'ti ti-plus' }}"></i>
                    {{ $isEdit ? 'Mode Edit' : 'Mode Tambah' }}
                </span>
            </div>

            @if (! empty($errors))
                <div class="barang-alert barang-alert-error">
                    <i class="ti ti-alert-triangle"></i>
                    <span>Masih ada input yang perlu diperbaiki.</span>
                </div>
            @endif

            <form action="{{ url($formAction) }}" method="POST" class="barang-form" data-barang-form>
                @csrf

                <div class="barang-form-grid">
                    <div class="barang-field field-full">
                        <label for="barcode">Barcode <span>*</span></label>
                        <div class="barang-input-wrap barang-input-wrap-with-action">
                            <i class="ti ti-barcode"></i>
                            <input type="text"
                                   id="barcode"
                                   name="barcode"
                                   value="{{ $barcode }}"
                                   placeholder="Scan / ketik / klik Generate"
                                   class="{{ $fieldErr('barcode') }}"
                                   autocomplete="off"
                                   autofocus
                                   data-barang-barcode-input>
                            <button type="button"
                                    class="barang-btn barang-btn-soft barang-btn-generate"
                                    data-barang-generate-barcode
                                    data-generate-url="{{ url('/admin/barang/generate-barcode') }}"
                                    title="Generate barcode otomatis">
                                <i class="ti ti-wand"></i>
                                <span>Generate</span>
                            </button>
                        </div>

                        <small class="barang-field-hint">
                            Scan barcode dari kemasan, ketik manual, atau klik <strong>Generate</strong>.
                        </small>

                        @if (! empty($barcode))
                            <div class="barang-barcode-preview" data-barang-barcode-preview>
                                <svg id="barangBarcodePreview" class="barang-barcode-svg" data-barcode-value="{{ $barcode }}"></svg>
                            </div>
                        @else
                            <div class="barang-barcode-preview is-empty" data-barang-barcode-preview>
                                <svg id="barangBarcodePreview" class="barang-barcode-svg" data-barcode-value=""></svg>
                                <span class="barang-barcode-empty-text">Preview barcode akan muncul setelah barcode diisi.</span>
                            </div>
                        @endif

                        @if (isset($errors['barcode']))
                            <small class="barang-field-error">{{ $errors['barcode'] }}</small>
                        @endif
                    </div>

                    <div class="barang-field field-full">
                        <label for="kode_barang">Kode Barang <span>*</span></label>
                        <div class="barang-input-wrap">
                            <i class="ti ti-hash"></i>
                            <input type="text"
                                   id="kode_barang"
                                   name="kode_barang"
                                   value="{{ $kodeBarang }}"
                                   placeholder="Contoh: BRG001"
                                   class="{{ $fieldErr('kode_barang') }}"
                                   autocomplete="off">
                        </div>
                        @if (isset($errors['kode_barang']))
                            <small class="barang-field-error">{{ $errors['kode_barang'] }}</small>
                        @endif
                    </div>

                    <div class="barang-field field-full">
                        <label for="nama">Nama Barang <span>*</span></label>
                        <div class="barang-input-wrap">
                            <i class="ti ti-package"></i>
                            <input type="text"
                                   id="nama"
                                   name="nama"
                                   value="{{ $nama }}"
                                   placeholder="Contoh: Pensil 2B"
                                   class="{{ $fieldErr('nama') }}"
                                   autocomplete="off">
                        </div>
                        @if (isset($errors['nama']))
                            <small class="barang-field-error">{{ $errors['nama'] }}</small>
                        @endif
                    </div>

                    <div class="barang-field">
                        <label for="id_kategori">Kategori <span>*</span></label>
                        <div class="barang-input-wrap">
                            <i class="ti ti-folder"></i>
                            <select id="id_kategori" name="id_kategori" class="{{ $fieldErr('id_kategori') }}">
                                <option value="">Pilih kategori</option>
                                @foreach ($kategoris as $k)
                                    <option value="{{ $k['id'] ?? '' }}"
                                            {{ (string) $idKategori === (string) ($k['id'] ?? '') ? 'selected' : '' }}>
                                        {{ $k['nama'] ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if (isset($errors['id_kategori']))
                            <small class="barang-field-error">{{ $errors['id_kategori'] }}</small>
                        @endif
                    </div>

                    <div class="barang-field">
                        <label for="satuan">Satuan <span>*</span></label>
                        <div class="barang-input-wrap">
                            <i class="ti ti-ruler"></i>
                            <input type="text"
                                   id="satuan"
                                   name="satuan"
                                   value="{{ $satuan }}"
                                   placeholder="pcs, box, botol"
                                   class="{{ $fieldErr('satuan') }}"
                                   autocomplete="off">
                        </div>
                        @if (isset($errors['satuan']))
                            <small class="barang-field-error">{{ $errors['satuan'] }}</small>
                        @endif
                    </div>

                    <div class="barang-field">
                        <label for="harga_jual">Harga Jual <span>*</span></label>
                        <div class="barang-input-wrap">
                            <i class="ti ti-cash"></i>
                            <input type="number"
                                   id="harga_jual"
                                   name="harga_jual"
                                   value="{{ $hargaJual }}"
                                   min="1"
                                   step="1"
                                   placeholder="Contoh: 2000"
                                   class="{{ $fieldErr('harga_jual') }}"
                                   data-price-preview-input>
                        </div>
                        <small class="barang-field-hint" data-price-preview>Preview harga akan muncul di sini.</small>
                        @if (isset($errors['harga_jual']))
                            <small class="barang-field-error">{{ $errors['harga_jual'] }}</small>
                        @endif
                    </div>

                    <div class="barang-field">
                        <label for="stok_minimum">Stok Minimum <span>*</span></label>
                        <div class="barang-input-wrap">
                            <i class="ti ti-alert-triangle"></i>
                            <input type="number"
                                   id="stok_minimum"
                                   name="stok_minimum"
                                   value="{{ $stokMinimum }}"
                                   min="0"
                                   step="1"
                                   placeholder="Contoh: 5"
                                   class="{{ $fieldErr('stok_minimum') }}">
                        </div>
                        @if (isset($errors['stok_minimum']))
                            <small class="barang-field-error">{{ $errors['stok_minimum'] }}</small>
                        @endif
                    </div>

                    <div class="barang-field">
                        <label for="status">Status <span>*</span></label>
                        <div class="barang-input-wrap">
                            <i class="ti ti-toggle-right"></i>
                            <select id="status" name="status" class="{{ $fieldErr('status') }}">
                                <option value="aktif" {{ $status === 'aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="nonaktif" {{ $status === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        @if (isset($errors['status']))
                            <small class="barang-field-error">{{ $errors['status'] }}</small>
                        @endif
                    </div>

                    @if ($isEdit)
                        <div class="barang-field">
                            <label>Stok Saat Ini</label>
                            <div class="barang-input-wrap is-readonly">
                                <i class="ti ti-stack"></i>
                                <input type="text" value="{{ $barang['stok'] ?? 0 }}" disabled>
                            </div>
                            <small class="barang-field-hint">Readonly. Ubah stok lewat menu restock atau transaksi.</small>
                        </div>
                    @endif
                </div>

                <div class="barang-form-actions">
                    <button type="submit" class="barang-btn barang-btn-primary">
                        <i class="ti ti-device-floppy"></i>
                        Simpan
                    </button>
                    <a href="{{ url('/admin/barang') }}" class="barang-btn barang-btn-ghost">
                        <i class="ti ti-x"></i>
                        Batal
                    </a>
                </div>
            </form>
        </article>

        <aside class="barang-form-aside">
            <div class="barang-info-card">
                <span class="barang-info-icon"><i class="ti ti-info-circle"></i></span>
                <h4>Catatan Stok</h4>
                <ul>
                    <li><i class="ti ti-stack-push"></i> Stok masuk lewat menu Restock.</li>
                    <li><i class="ti ti-shopping-cart"></i> Stok keluar lewat Transaksi.</li>
                    <li><i class="ti ti-alert-triangle"></i> Stok minimum dipakai untuk penanda stok menipis.</li>
                </ul>
            </div>
        </aside>
    </section>
</div>
@endsection
