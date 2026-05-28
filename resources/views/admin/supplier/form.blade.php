@php
    $pageCss = ['assets/css/supplier.css'];
    $pageScripts = ['assets/js/supplier.js'];

    $formAction = $formAction ?? '/admin/supplier/store';
    $formMode = $formMode ?? 'create';
    $supplier = $supplier ?? null;
    $errors = $errors ?? [];
    $old = $old ?? [];

    $nama = $old['nama'] ?? ($supplier['nama'] ?? '');
    $kontakPerson = $old['kontak_person'] ?? ($supplier['kontak_person'] ?? '');
    $noHp = $old['no_hp'] ?? ($supplier['no_hp'] ?? '');
    $alamat = $old['alamat'] ?? ($supplier['alamat'] ?? '');
    $keterangan = $old['keterangan'] ?? ($supplier['keterangan'] ?? '');
    $status = $old['status'] ?? ($supplier['status'] ?? 'aktif');

    $isEdit = $formMode === 'edit';
    $fieldErr = fn ($f) => isset($errors[$f]) ? ' is-invalid' : '';
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Form Supplier',
    'activeMenu' => $activeMenu ?? 'supplier',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="supplier-page">
    <section class="supplier-hero supplier-form-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="supplier-hero-content">
            <span class="supplier-eyebrow">
                <i class="{{ $isEdit ? 'ti ti-edit' : 'ti ti-truck-delivery' }}"></i>
                {{ $isEdit ? 'Edit Master Supplier' : 'Tambah Master Supplier' }}
            </span>
            <h2>{{ $title }}</h2>
            <p>
                {{ $isEdit
                    ? 'Perbarui data supplier.'
                    : 'Tambahkan supplier baru supaya proses restock punya sumber barang yang jelas.' }}
            </p>
        </div>

        <div class="supplier-hero-actions">
            <a href="{{ url('/admin/supplier') }}" class="supplier-btn supplier-btn-soft">
                <i class="ti ti-arrow-left"></i>
                Kembali
            </a>
        </div>
    </section>

    <section class="supplier-form-layout" data-aos="fade-up" data-aos-delay="150">
        <article class="supplier-form-card">
            <div class="supplier-form-head">
                <div>
                    <span>Form Supplier</span>
                    <h3>{{ $isEdit ? 'Edit Data Supplier' : 'Tambah Supplier Baru' }}</h3>
                </div>
                <span class="supplier-form-badge">
                    <i class="{{ $isEdit ? 'ti ti-pencil' : 'ti ti-plus' }}"></i>
                    {{ $isEdit ? 'Mode Edit' : 'Mode Tambah' }}
                </span>
            </div>

            @if (! empty($errors))
                <div class="supplier-alert supplier-alert-error">
                    <i class="ti ti-alert-triangle"></i>
                    <span>Masih ada input yang perlu dibenerin.</span>
                </div>
            @endif

            <form action="{{ url($formAction) }}" method="POST" class="supplier-form" data-supplier-form>
                @csrf

                <div class="supplier-form-grid">
                    <div class="supplier-field field-full">
                        <label for="nama">Nama Supplier <span>*</span></label>
                        <div class="supplier-input-wrap">
                            <i class="ti ti-building-store"></i>
                            <input type="text" id="nama" name="nama"
                                   value="{{ $nama }}"
                                   placeholder="Contoh: CV Sumber Makmur"
                                   class="{{ $fieldErr('nama') }}"
                                   maxlength="150" autocomplete="off" autofocus>
                        </div>
                        <div class="supplier-field-footer">
                            <small class="supplier-field-hint">Wajib diisi. Maksimal 150 karakter.</small>
                            <small class="supplier-counter" data-supplier-counter>0/150</small>
                        </div>
                        @if (isset($errors['nama']))
                            <small class="supplier-field-error">{{ $errors['nama'] }}</small>
                        @endif
                    </div>

                    <div class="supplier-field">
                        <label for="kontak_person">Kontak Person</label>
                        <div class="supplier-input-wrap">
                            <i class="ti ti-user"></i>
                            <input type="text" id="kontak_person" name="kontak_person"
                                   value="{{ $kontakPerson }}"
                                   placeholder="Nama orang yang bisa dihubungi"
                                   class="{{ $fieldErr('kontak_person') }}"
                                   maxlength="100" autocomplete="off">
                        </div>
                        @if (isset($errors['kontak_person']))
                            <small class="supplier-field-error">{{ $errors['kontak_person'] }}</small>
                        @endif
                    </div>

                    <div class="supplier-field">
                        <label for="no_hp">No HP</label>
                        <div class="supplier-input-wrap">
                            <i class="ti ti-phone"></i>
                            <input type="text" id="no_hp" name="no_hp"
                                   value="{{ $noHp }}"
                                   placeholder="Contoh: 08123456789"
                                   class="{{ $fieldErr('no_hp') }}"
                                   maxlength="20" autocomplete="off">
                        </div>
                        <small class="supplier-field-hint">Maksimal 20 karakter.</small>
                        @if (isset($errors['no_hp']))
                            <small class="supplier-field-error">{{ $errors['no_hp'] }}</small>
                        @endif
                    </div>

                    <div class="supplier-field field-full">
                        <label for="alamat">Alamat</label>
                        <div class="supplier-textarea-wrap">
                            <i class="ti ti-map-pin"></i>
                            <textarea id="alamat" name="alamat" rows="5"
                                      placeholder="Alamat supplier"
                                      class="{{ $fieldErr('alamat') }}">{{ $alamat }}</textarea>
                        </div>
                        @if (isset($errors['alamat']))
                            <small class="supplier-field-error">{{ $errors['alamat'] }}</small>
                        @endif
                    </div>

                    <div class="supplier-field field-full">
                        <label for="keterangan">Keterangan</label>
                        <div class="supplier-textarea-wrap">
                            <i class="ti ti-notes"></i>
                            <textarea id="keterangan" name="keterangan" rows="5"
                                      placeholder="Catatan tambahan, boleh dikosongkan"
                                      class="{{ $fieldErr('keterangan') }}">{{ $keterangan }}</textarea>
                        </div>
                        @if (isset($errors['keterangan']))
                            <small class="supplier-field-error">{{ $errors['keterangan'] }}</small>
                        @endif
                    </div>

                    <div class="supplier-field">
                        <label for="status">Status <span>*</span></label>
                        <div class="supplier-input-wrap">
                            <i class="ti ti-toggle-right"></i>
                            <select id="status" name="status" class="{{ $fieldErr('status') }}">
                                <option value="aktif" {{ $status === 'aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="nonaktif" {{ $status === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        @if (isset($errors['status']))
                            <small class="supplier-field-error">{{ $errors['status'] }}</small>
                        @endif
                    </div>
                </div>

                <div class="supplier-form-actions">
                    <button type="submit" class="supplier-btn supplier-btn-primary supplier-submit-btn">
                        <i class="ti ti-device-floppy"></i>
                        Simpan
                    </button>
                    <a href="{{ url('/admin/supplier') }}" class="supplier-btn supplier-btn-ghost">
                        <i class="ti ti-x"></i>
                        Batal
                    </a>
                </div>
            </form>
        </article>

        <aside class="supplier-form-aside">
            <div class="supplier-info-card">
                <span class="supplier-info-icon"><i class="ti ti-info-circle"></i></span>
                <h4>Catatan Supplier</h4>
                <ul>
                    <li><i class="ti ti-building-store"></i> Nama supplier wajib diisi.</li>
                    <li><i class="ti ti-stack-push"></i> Supplier aktif akan tampil di form restock.</li>
                    <li><i class="ti ti-user-off"></i> Supplier nonaktif tidak dipakai untuk restock baru.</li>
                </ul>
            </div>
        </aside>
    </section>
</div>
@endsection
