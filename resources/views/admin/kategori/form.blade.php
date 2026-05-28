@php
    $pageCss = ['assets/css/kategori.css'];
    $pageScripts = ['assets/js/kategori.js'];

    $formAction = $formAction ?? '/admin/kategori/store';
    $formMode = $formMode ?? 'create';
    $kategori = $kategori ?? null;
    $errors = $errors ?? [];
    $old = $old ?? [];

    $nama = $old['nama'] ?? ($kategori['nama'] ?? '');
    $deskripsi = $old['deskripsi'] ?? ($kategori['deskripsi'] ?? '');
    $isEdit = $formMode === 'edit';

    $fieldErrorClass = fn ($field) => isset($errors[$field]) ? ' is-invalid' : '';
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Form Kategori',
    'activeMenu' => $activeMenu ?? 'kategori',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="kategori-page">
    <section class="kategori-hero kategori-form-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="kategori-hero-content">
            <span class="kategori-eyebrow">
                <i class="{{ $isEdit ? 'ti ti-edit' : 'ti ti-folder-plus' }}"></i>
                {{ $isEdit ? 'Edit Master Kategori' : 'Tambah Master Kategori' }}
            </span>

            <h2>{{ $title }}</h2>

            <p>
                {{ $isEdit
                    ? 'Perbarui nama dan deskripsi kategori.'
                    : 'Tambahkan kategori baru supaya barang bisa dikelompokkan dengan benar.' }}
            </p>
        </div>

        <div class="kategori-hero-actions">
            <a href="{{ url('/admin/kategori') }}" class="kategori-btn kategori-btn-soft">
                <i class="ti ti-arrow-left"></i>
                Kembali
            </a>
        </div>
    </section>

    <section class="kategori-form-layout" data-aos="fade-up" data-aos-delay="150">
        <article class="kategori-form-card">
            <div class="kategori-form-head">
                <div>
                    <span>Form Kategori</span>
                    <h3>{{ $isEdit ? 'Edit Data Kategori' : 'Tambah Kategori Baru' }}</h3>
                </div>

                <span class="kategori-form-badge">
                    <i class="{{ $isEdit ? 'ti ti-pencil' : 'ti ti-plus' }}"></i>
                    {{ $isEdit ? 'Mode Edit' : 'Mode Tambah' }}
                </span>
            </div>

            @if (! empty($errors))
                <div class="kategori-alert kategori-alert-error">
                    <i class="ti ti-alert-triangle"></i>
                    <span>Masih ada input yang salah. Perbaiki dulu.</span>
                </div>
            @endif

            <form action="{{ url($formAction) }}" method="POST" class="kategori-form" data-kategori-form>
                @csrf

                <div class="kategori-form-grid">
                    <div class="kategori-field">
                        <label for="nama">
                            Nama Kategori <span>*</span>
                        </label>

                        <div class="kategori-input-wrap">
                            <i class="ti ti-folder"></i>
                            <input
                                type="text"
                                id="nama"
                                name="nama"
                                value="{{ $nama }}"
                                placeholder="Contoh: Alat Tulis"
                                class="{{ $fieldErrorClass('nama') }}"
                                maxlength="100"
                                autocomplete="off"
                                autofocus
                            >
                        </div>

                        <div class="kategori-field-footer">
                            <small class="kategori-field-hint">Maksimal 100 karakter.</small>
                            <small class="kategori-counter" data-kategori-counter>0/100</small>
                        </div>

                        @if (isset($errors['nama']))
                            <small class="kategori-field-error">{{ $errors['nama'] }}</small>
                        @endif
                    </div>

                    <div class="kategori-field field-full">
                        <label for="deskripsi">Deskripsi</label>

                        <div class="kategori-textarea-wrap">
                            <i class="ti ti-align-left"></i>
                            <textarea
                                id="deskripsi"
                                name="deskripsi"
                                rows="6"
                                placeholder="Contoh: Kategori untuk pensil, pulpen, buku, penghapus."
                                class="{{ $fieldErrorClass('deskripsi') }}"
                            >{{ $deskripsi }}</textarea>
                        </div>

                        @if (isset($errors['deskripsi']))
                            <small class="kategori-field-error">{{ $errors['deskripsi'] }}</small>
                        @endif
                    </div>
                </div>

                <div class="kategori-form-actions">
                    <button type="submit" class="kategori-btn kategori-btn-primary kategori-submit-btn">
                        <i class="ti ti-device-floppy"></i>
                        Simpan
                    </button>

                    <a href="{{ url('/admin/kategori') }}" class="kategori-btn kategori-btn-ghost">
                        <i class="ti ti-x"></i>
                        Batal
                    </a>
                </div>
            </form>
        </article>

        <aside class="kategori-form-aside">
            <div class="kategori-info-card">
                <span class="kategori-info-icon"><i class="ti ti-info-circle"></i></span>
                <h4>Catatan Kategori</h4>
                <ul>
                    <li><i class="ti ti-folder"></i> Nama kategori wajib diisi.</li>
                    <li><i class="ti ti-package"></i> Kategori dipakai saat tambah/edit barang.</li>
                    <li><i class="ti ti-trash-off"></i> Kategori yang masih dipakai barang tidak bisa dihapus.</li>
                </ul>
            </div>
        </aside>
    </section>
</div>
@endsection
