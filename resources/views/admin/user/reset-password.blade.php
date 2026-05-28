@php
    $pageCss = ['assets/css/user.css'];
    $pageScripts = ['assets/js/user.js'];

    $userData = $userData ?? [];
    $errors = $errors ?? [];

    $id = (int) ($userData['id'] ?? 0);
    $username = (string) ($userData['username'] ?? '-');
    $email = (string) ($userData['email'] ?? '-');

    $fieldErr = fn ($f) => isset($errors[$f]) ? ' is-invalid' : '';
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Reset Password Kasir',
    'activeMenu' => $activeMenu ?? 'user',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="user-page user-reset-page">
    <section class="user-hero user-form-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="user-hero-content">
            <span class="user-eyebrow">
                <i class="ti ti-key"></i>
                Reset Password
            </span>
            <h2>Reset Password Kasir</h2>
            <p>Ganti password untuk akun kasir.</p>
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
                    <span>Password</span>
                    <h3>Reset Password</h3>
                </div>
                <span class="user-form-badge">
                    <i class="ti ti-key"></i>
                    Keamanan
                </span>
            </div>

            <div class="user-account-preview">
                <span><i class="ti ti-user"></i></span>
                <div>
                    <strong>{{ $username }}</strong>
                    <small>{{ $email }}</small>
                </div>
            </div>

            @if (! empty($errors))
                <div class="user-alert user-alert-error">
                    <i class="ti ti-alert-triangle"></i>
                    <span>Password belum valid.</span>
                </div>
            @endif

            <form action="{{ url('/admin/user/reset-password/'.$id) }}" method="POST" class="user-form" data-user-form>
                @csrf
                <div class="user-form-grid">
                    <div class="user-field">
                        <label for="password">Password Baru <span>*</span></label>
                        <div class="user-input-wrap">
                            <i class="ti ti-lock"></i>
                            <input type="password" id="password" name="password"
                                   placeholder="Minimal 8 karakter"
                                   class="{{ $fieldErr('password') }}"
                                   data-password-input autofocus>
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
                        <label for="password_confirmation">Konfirmasi Password Baru <span>*</span></label>
                        <div class="user-input-wrap">
                            <i class="ti ti-lock-check"></i>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   placeholder="Ulangi password baru"
                                   class="{{ $fieldErr('password_confirmation') }}">
                            <button type="button" class="user-toggle-password" data-toggle-password aria-label="Tampilkan password">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                        @if (isset($errors['password_confirmation']))
                            <small class="user-field-error">{{ $errors['password_confirmation'] }}</small>
                        @endif
                    </div>
                </div>

                <div class="user-form-actions">
                    <button type="submit" class="user-btn user-btn-submit user-submit-btn">
                        <i class="ti ti-device-floppy"></i>
                        Simpan Password
                    </button>
                    <a href="{{ url('/admin/user') }}" class="user-btn user-btn-ghost">
                        <i class="ti ti-x"></i>
                        Batal
                    </a>
                </div>
            </form>
        </article>

        <aside class="user-form-aside">
            <div class="user-info-card">
                <span class="user-info-icon"><i class="ti ti-shield-lock"></i></span>
                <h4>Tips Password</h4>
                <p>Password baru minimal 8 karakter. Lebih panjang lebih aman.</p>
                <ul>
                    <li><i class="ti ti-check"></i> Pakai kombinasi huruf dan angka.</li>
                    <li><i class="ti ti-check"></i> Jangan pakai username sebagai password.</li>
                    <li><i class="ti ti-check"></i> Beri tahu kasir password barunya secara aman.</li>
                </ul>
            </div>
        </aside>
    </section>
</div>
@endsection
