@php
    $pageCss = ['assets/css/user.css'];
    $pageScripts = ['assets/js/user.js'];

    $formAction = $formAction ?? '/admin/user/store';
    $formMode = $formMode ?? 'create';
    $userData = $userData ?? null;
    $errors = $errors ?? [];
    $old = $old ?? [];

    $isEdit = $formMode === 'edit';
    $username = $old['username'] ?? ($userData['username'] ?? '');
    $email = $old['email'] ?? ($userData['email'] ?? '');
    $status = $old['status'] ?? ($userData['status'] ?? 'aktif');

    $fieldErr = fn ($f) => isset($errors[$f]) ? ' is-invalid' : '';
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Form Kasir',
    'activeMenu' => $activeMenu ?? 'user',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="user-page">
    <section class="user-hero user-form-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="user-hero-content">
            <span class="user-eyebrow">
                <i class="{{ $isEdit ? 'ti ti-edit' : 'ti ti-user-plus' }}"></i>
                {{ $isEdit ? 'Edit Akun Kasir' : 'Tambah Akun Kasir' }}
            </span>
            <h2>{{ $title }}</h2>
            <p>
                {{ $isEdit
                    ? 'Perbarui username, email, dan status kasir. Password direset dari halaman terpisah.'
                    : 'Buat akun kasir baru untuk login POS. Password minimal 8 karakter.' }}
            </p>
        </div>

        <div class="user-hero-actions">
            <a href="{{ url('/admin/user') }}" class="user-btn user-btn-soft">
                <i class="ti ti-arrow-left"></i>
                Kembali
            </a>
        </div>
    </section>

    <section class="user-form-layout" data-aos="fade-up" data-aos-delay="150">
        <article class="user-form-card">
            <div class="user-form-head">
                <div>
                    <span>Form Kasir</span>
                    <h3>{{ $isEdit ? 'Edit Data Kasir' : 'Tambah Kasir Baru' }}</h3>
                </div>
                <span class="user-form-badge">
                    <i class="{{ $isEdit ? 'ti ti-pencil' : 'ti ti-plus' }}"></i>
                    {{ $isEdit ? 'Mode Edit' : 'Mode Tambah' }}
                </span>
            </div>

            @if (! empty($errors))
                <div class="user-alert user-alert-error">
                    <i class="ti ti-alert-triangle"></i>
                    <span>Masih ada input yang perlu dibenerin.</span>
                </div>
            @endif

            <form action="{{ url($formAction) }}" method="POST" class="user-form" data-user-form>
                @csrf
                <div class="user-form-grid">
                    <div class="user-field">
                        <label for="username">Username <span>*</span></label>
                        <div class="user-input-wrap">
                            <i class="ti ti-user"></i>
                            <input type="text" id="username" name="username"
                                   value="{{ $username }}"
                                   placeholder="Contoh: kasir01"
                                   maxlength="50" autocomplete="off" autofocus
                                   class="{{ $fieldErr('username') }}">
                        </div>
                        <div class="user-field-footer">
                            <small class="user-field-hint">Wajib diisi. Maksimal 50 karakter.</small>
                            <small class="user-counter" data-user-counter>0/50</small>
                        </div>
                        @if (isset($errors['username']))
                            <small class="user-field-error">{{ $errors['username'] }}</small>
                        @endif
                    </div>

                    <div class="user-field">
                        <label for="email">Email <span>*</span></label>
                        <div class="user-input-wrap">
                            <i class="ti ti-mail"></i>
                            <input type="email" id="email" name="email"
                                   value="{{ $email }}"
                                   placeholder="kasir@email.com"
                                   maxlength="100" autocomplete="off"
                                   class="{{ $fieldErr('email') }}">
                        </div>
                        @if (isset($errors['email']))
                            <small class="user-field-error">{{ $errors['email'] }}</small>
                        @endif
                    </div>

                    <div class="user-field">
                        <label for="status">Status <span>*</span></label>
                        <div class="user-input-wrap">
                            <i class="ti ti-toggle-right"></i>
                            <select id="status" name="status" class="{{ $fieldErr('status') }}">
                                <option value="aktif" {{ $status === 'aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="nonaktif" {{ $status === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        @if (isset($errors['status']))
                            <small class="user-field-error">{{ $errors['status'] }}</small>
                        @endif
                    </div>

                    @if (! $isEdit)
                        <div class="user-field">
                            <label for="password">Password <span>*</span></label>
                            <div class="user-input-wrap">
                                <i class="ti ti-lock"></i>
                                <input type="password" id="password" name="password"
                                       placeholder="Minimal 8 karakter"
                                       class="{{ $fieldErr('password') }}"
                                       data-password-input>
                                <button type="button" class="user-toggle-password" data-toggle-password aria-label="Tampilkan password">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                            <div class="user-password-meter">
                                <span data-password-meter></span>
                            </div>
                            <small class="user-field-hint" data-password-hint>Minimal 8 karakter.</small>
                            @if (isset($errors['password']))
                                <small class="user-field-error">{{ $errors['password'] }}</small>
                            @endif
                        </div>

                        <div class="user-field">
                            <label for="password_confirmation">Konfirmasi Password <span>*</span></label>
                            <div class="user-input-wrap">
                                <i class="ti ti-lock-check"></i>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                       placeholder="Ulangi password"
                                       class="{{ $fieldErr('password_confirmation') }}">
                                <button type="button" class="user-toggle-password" data-toggle-password aria-label="Tampilkan password">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                            @if (isset($errors['password_confirmation']))
                                <small class="user-field-error">{{ $errors['password_confirmation'] }}</small>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="user-form-actions">
                    <button type="submit" class="user-btn user-btn-submit user-submit-btn">
                        <i class="ti ti-device-floppy"></i>
                        Simpan
                    </button>

                    <a href="{{ url('/admin/user') }}" class="user-btn user-btn-ghost">
                        <i class="ti ti-x"></i>
                        Batal
                    </a>

                    @if ($isEdit && ! empty($userData['id']))
                        <a href="{{ url('/admin/user/reset-password/'.$userData['id']) }}" class="user-btn user-btn-warning">
                            <i class="ti ti-key"></i>
                            Reset Password
                        </a>
                    @endif
                </div>
            </form>
        </article>

        <aside class="user-form-aside">
            <div class="user-info-card">
                <span class="user-info-icon"><i class="ti ti-info-circle"></i></span>
                <h4>Catatan Akun</h4>
                <p>Akun dari menu ini selalu dibuat sebagai kasir. Admin utama tidak dikelola dari sini.</p>
                <ul>
                    <li><i class="ti ti-cash-register"></i> Kasir aktif bisa login dan memakai POS.</li>
                    <li><i class="ti ti-user-off"></i> Kasir nonaktif tidak dipakai untuk operasional.</li>
                    <li><i class="ti ti-key"></i> Password edit lewat halaman reset.</li>
                </ul>
            </div>
        </aside>
    </section>
</div>
@endsection
