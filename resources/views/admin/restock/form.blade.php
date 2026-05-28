@php
    $pageCss = ['assets/css/restock.css'];
    $pageScripts = ['assets/js/restock.js'];

    $formAction = $formAction ?? '/admin/restock/store';
    $tipe = $tipe ?? 'masuk';
    $barangs = $barangs ?? [];
    $suppliers = $suppliers ?? [];
    $errors = $errors ?? [];
    $old = $old ?? [];

    $tanggal = $old['tanggal'] ?? date('Y-m-d');
    $idBarang = $old['id_barang'] ?? '';
    $idSupplier = $old['id_supplier'] ?? '';
    $qty = $old['qty'] ?? '';
    $hargaBeli = $old['harga_beli'] ?? '';
    $hargaJualBaru = $old['harga_jual_baru'] ?? '';
    $catatan = $old['catatan'] ?? '';
    $alasan = $old['alasan'] ?? '';

    $isMasuk = $tipe === 'masuk';
    $isKeluar = $tipe === 'keluar';
    $fieldErr = fn ($f) => isset($errors[$f]) ? ' is-invalid' : '';
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Form Restock',
    'activeMenu' => $activeMenu ?? 'restock',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="restock-page">
    <section class="restock-hero restock-form-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="restock-hero-content">
            <span class="restock-eyebrow">
                <i class="ti ti-{{ $isMasuk ? 'stack-push' : 'stack-pop' }}"></i>
                {{ $isMasuk ? 'Tambah Stok Masuk' : 'Kurangi Stok' }}
            </span>
            <h2>{{ $title }}</h2>
        </div>

        <div class="restock-hero-actions">
            <a href="{{ url('/admin/restock') }}" class="restock-btn restock-btn-soft">
                <i class="ti ti-arrow-left"></i>
                Kembali
            </a>
        </div>
    </section>

    <section class="restock-form-layout" data-aos="fade-up" data-aos-delay="150">
        <article class="restock-form-card">
            <div class="restock-form-head">
                <div>
                    <span>Form {{ $isMasuk ? 'Restock' : 'Pengurangan Stok' }}</span>
                    <h3>{{ $isMasuk ? 'Tambah Restock Barang' : 'Kurangi Stok Barang' }}</h3>
                </div>
                <span class="restock-form-badge {{ $isKeluar ? 'badge-danger' : '' }}">
                    <i class="ti ti-{{ $isMasuk ? 'plus' : 'minus' }}"></i>
                    {{ $isMasuk ? 'Stok Masuk' : 'Stok Keluar' }}
                </span>
            </div>

            @if (! empty($errors))
                <div class="restock-alert restock-alert-error">
                    <i class="ti ti-alert-triangle"></i>
                </div>
            @endif

            <form action="{{ url($formAction) }}" method="POST" class="restock-form" data-restock-form>
                @csrf
                <input type="hidden" name="tipe" value="{{ $tipe }}">

                <div class="restock-form-grid">
                    <div class="restock-field">
                        <label for="tanggal">Tanggal <span>*</span></label>
                        <div class="restock-input-wrap">
                            <i class="ti ti-calendar"></i>
                            <input type="date" id="tanggal" name="tanggal"
                                   value="{{ $tanggal }}"
                                   class="{{ $fieldErr('tanggal') }}">
                        </div>
                        @if (isset($errors['tanggal']))
                            <small class="restock-field-error">{{ $errors['tanggal'] }}</small>
                        @endif
                    </div>

                    <div class="restock-field">
                        <label for="id_supplier">
                            Supplier @if ($isMasuk)<span>*</span>@endif
                        </label>
                        <div class="restock-input-wrap">
                            <i class="ti ti-truck-delivery"></i>
                            <select id="id_supplier" name="id_supplier"
                                    class="{{ $fieldErr('id_supplier') }}"
                                    data-supplier-select>
                                <option value="">{{ $isKeluar ? 'Opsional (pilih jika perlu)' : 'Pilih supplier' }}</option>
                                @foreach ($suppliers as $s)
                                    <option value="{{ $s['id'] ?? '' }}"
                                            data-name="{{ $s['nama'] ?? '-' }}"
                                            data-contact="{{ $s['kontak_person'] ?? '-' }}"
                                            data-phone="{{ $s['no_hp'] ?? '-' }}"
                                            {{ (string) $idSupplier === (string) ($s['id'] ?? '') ? 'selected' : '' }}>
                                        {{ $s['nama'] ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if (isset($errors['id_supplier']))
                            <small class="restock-field-error">{{ $errors['id_supplier'] }}</small>
                        @endif
                    </div>

                    <div class="restock-field field-full">
                        <label for="id_barang">Barang <span>*</span></label>
                        <div class="restock-input-wrap">
                            <i class="ti ti-package"></i>
                            <select id="id_barang" name="id_barang"
                                    class="{{ $fieldErr('id_barang') }}"
                                    data-barang-select>
                                <option value="">Pilih barang</option>
                                @foreach ($barangs as $b)
                                    <option value="{{ $b['id'] ?? '' }}"
                                            data-name="{{ $b['nama'] ?? '-' }}"
                                            data-code="{{ $b['kode_barang'] ?? '-' }}"
                                            data-stock="{{ $b['stok'] ?? 0 }}"
                                            data-unit="{{ $b['satuan'] ?? '-' }}"
                                            data-price="{{ number_format((float) ($b['harga_jual'] ?? 0), 0, '.', '') }}"
                                            {{ (string) $idBarang === (string) ($b['id'] ?? '') ? 'selected' : '' }}>
                                        {{ $b['kode_barang'] ?? '-' }} - {{ $b['nama'] ?? '-' }}
                                        | Stok: {{ $b['stok'] ?? 0 }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if (isset($errors['id_barang']))
                            <small class="restock-field-error">{{ $errors['id_barang'] }}</small>
                        @endif
                    </div>

                    <div class="restock-field">
                        <label for="qty">{{ $isMasuk ? 'Qty Masuk' : 'Qty Keluar' }} <span>*</span></label>
                        <div class="restock-input-wrap">
                            <i class="ti ti-{{ $isMasuk ? 'plus' : 'minus' }}"></i>
                            <input type="number" id="qty" name="qty"
                                   value="{{ $qty }}" min="1" step="1"
                                   placeholder="{{ $isMasuk ? 'Contoh: 20' : 'Contoh: 5' }}"
                                   class="{{ $fieldErr('qty') }}"
                                   data-qty-input>
                        </div>
                        @if (isset($errors['qty']))
                            <small class="restock-field-error">{{ $errors['qty'] }}</small>
                        @endif
                    </div>

                    <div class="restock-field">
                        <label for="harga_beli">Harga Beli per Item <span>*</span></label>
                        <div class="restock-input-wrap">
                            <i class="ti ti-cash"></i>
                            <input type="number" id="harga_beli" name="harga_beli"
                                   value="{{ $hargaBeli }}" min="1" step="1"
                                   placeholder="Contoh: 1500"
                                   class="{{ $fieldErr('harga_beli') }}"
                                   data-buy-price-input>
                        </div>
                        <small class="restock-field-hint" data-buy-price-preview>
                            Preview harga beli akan muncul di sini.
                        </small>
                        @if (isset($errors['harga_beli']))
                            <small class="restock-field-error">{{ $errors['harga_beli'] }}</small>
                        @endif
                    </div>

                    @if ($isMasuk)
                        <div class="restock-field">
                            <label for="harga_jual_baru">Harga Jual Baru</label>
                            <div class="restock-input-wrap">
                                <i class="ti ti-tag"></i>
                                <input type="number" id="harga_jual_baru" name="harga_jual_baru"
                                       value="{{ $hargaJualBaru }}" min="1" step="1"
                                       placeholder="Kosongkan kalau tidak berubah"
                                       class="{{ $fieldErr('harga_jual_baru') }}"
                                       data-new-price-input>
                            </div>
                            @if (isset($errors['harga_jual_baru']))
                                <small class="restock-field-error">{{ $errors['harga_jual_baru'] }}</small>
                            @endif
                        </div>
                    @endif

                    @if ($isKeluar)
                        <div class="restock-field field-full">
                            <label for="alasan">Alasan Pengurangan <span>*</span></label>
                            <div class="restock-input-wrap">
                                <i class="ti ti-alert-circle"></i>
                                <select id="alasan_preset" data-alasan-preset>
                                    <option value="">Pilih alasan atau tulis sendiri</option>
                                    @foreach (['Barang rusak','Barang expired','Barang hilang','Koreksi stok','Salah input'] as $opt)
                                        <option value="{{ $opt }}" {{ $alasan === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                    <option value="custom">Tulis sendiri...</option>
                                </select>
                            </div>
                            <div class="restock-textarea-wrap" style="margin-top: 8px;">
                                <i class="ti ti-notes"></i>
                                <textarea id="alasan" name="alasan" rows="3"
                                          placeholder="Tulis alasan pengurangan stok"
                                          class="{{ $fieldErr('alasan') }}"
                                          data-alasan-input>{{ $alasan }}</textarea>
                            </div>
                            @if (isset($errors['alasan']))
                                <small class="restock-field-error">{{ $errors['alasan'] }}</small>
                            @endif
                        </div>
                    @endif

                    <div class="restock-field field-full">
                        <label for="catatan">Catatan</label>
                        <div class="restock-textarea-wrap">
                            <i class="ti ti-notes"></i>
                            <textarea id="catatan" name="catatan" rows="5"
                                      placeholder="{{ $isMasuk ? 'Catatan tambahan, nomor nota, atau info pembelian' : 'Catatan tambahan (opsional)' }}"
                                      class="{{ $fieldErr('catatan') }}">{{ $catatan }}</textarea>
                        </div>
                        @if (isset($errors['catatan']))
                            <small class="restock-field-error">{{ $errors['catatan'] }}</small>
                        @endif
                    </div>
                </div>

                <div class="restock-total-box {{ $isKeluar ? 'is-keluar' : '' }}">
                    <div>
                        <span>{{ $isMasuk ? 'Total Nilai Restock' : 'Total Nilai Stok Keluar' }}</span>
                        <strong data-restock-total>Rp 0</strong>
                    </div>
                    <small>Total = qty × harga beli per item.</small>
                </div>

                <div class="restock-form-actions">
                    <button type="submit" class="restock-btn restock-btn-primary restock-submit-btn {{ $isKeluar ? 'btn-danger' : '' }}">
                        <i class="ti ti-device-floppy"></i>
                        {{ $isMasuk ? 'Simpan Restock' : 'Kurangi Stok' }}
                    </button>
                    <a href="{{ url('/admin/restock') }}" class="restock-btn restock-btn-ghost">
                        <i class="ti ti-x"></i>
                        Batal
                    </a>
                </div>
            </form>
        </article>

        <aside class="restock-form-aside">
            <div class="restock-info-card" data-barang-preview>
                <span class="restock-info-icon"><i class="ti ti-package"></i></span>
                <h4>Info Barang</h4>
                <ul>
                    <li><i class="ti ti-barcode"></i> <span data-preview-code>Kode: -</span></li>
                    <li><i class="ti ti-stack"></i> <span data-preview-stock>Stok saat ini: -</span></li>
                    <li><i class="ti ti-cash"></i> <span data-preview-price>Harga jual sekarang: -</span></li>
                </ul>
            </div>

            <div class="restock-info-card" data-supplier-preview>
                <span class="restock-info-icon icon-blue"><i class="ti ti-truck-delivery"></i></span>
                <h4>Info Supplier</h4>
                <ul>
                    <li><i class="ti ti-user"></i> <span data-preview-contact>Kontak: -</span></li>
                    <li><i class="ti ti-phone"></i> <span data-preview-phone>No HP: -</span></li>
                </ul>
            </div>

            @if ($isKeluar)
                <div class="restock-info-card restock-info-warning">
                    <span class="restock-info-icon icon-warning"><i class="ti ti-alert-triangle"></i></span>
                    <h4>Perhatian</h4>
                    <ul>
                        <li><i class="ti ti-x"></i> <span>Stok tidak boleh menjadi minus</span></li>
                        <li><i class="ti ti-pencil"></i> <span>Alasan pengurangan wajib diisi</span></li>
                        <li><i class="ti ti-history"></i> <span>Tercatat di riwayat stok</span></li>
                    </ul>
                </div>
            @endif
        </aside>
    </section>
</div>
@endsection
