@php
    $pageCss = ['assets/css/profil.css'];
    $pageScripts = ['assets/js/profil.js'];

    $authUser = $user ?? $currentUser ?? [];
    $userData = $userData ?? $authUser;
    $flash = $flash ?? [];
    $errors = $errors ?? [];

    $success = $flash['success'] ?? null;
    $error = $flash['error'] ?? null;

    $username = (string) ($userData['username'] ?? 'Kasir');
    $email = (string) ($userData['email'] ?? '-');
    $role = strtolower((string) ($userData['role'] ?? 'kasir'));
    $status = strtolower((string) ($userData['status'] ?? 'aktif'));
    $createdAt = (string) ($userData['created_at'] ?? '');
    $updatedAt = (string) ($userData['updated_at'] ?? '');

    $fieldErr = fn ($f) => isset($errors[$f]) ? ' is-invalid' : '';
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Profil Saya',
    'activeMenu' => $activeMenu ?? 'profil',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="profil-page">
    @if ($success)
        <div class="profil-alert profil-alert-success">
            <i class="ti ti-circle-check"></i>
            <span>{{ $success }}</span>
        </div>
    @endif

    @if ($error)
        <div class="profil-alert profil-alert-error">
            <i class="ti ti-alert-triangle"></i>
            <span>{{ $error }}</span>
        </div>
    @endif

    <section class="profil-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="profil-hero-content">
            <span class="profil-eyebrow">
                <i class="ti ti-user-circle"></i>
                Profil Kasir
            </span>
            <h2>Profil Saya</h2>
        </div>

        <div class="profil-hero-actions">
            <a href="{{ url('/kasir/dashboard') }}" class="profil-btn profil-btn-primary">
                <i class="ti ti-layout-dashboard"></i>
                Dashboard
            </a>
            <a href="{{ url('/kasir/transaksi') }}" class="profil-btn profil-btn-soft">
                <i class="ti ti-shopping-cart-plus"></i>
                POS
            </a>
        </div>
    </section>

    <section class="profil-overview" data-aos="fade-up" data-aos-delay="140">
        <article class="profil-card profil-identity-card">
            <div class="profil-avatar"><i class="ti ti-user"></i></div>
            <div class="profil-identity-content">
                <span>Akun Login</span>
                <h3>{{ $username }}</h3>
                <p>{{ $email }}</p>
            </div>
            <div class="profil-badges">
                <span class="profil-role">
                    <i class="ti ti-cash-register"></i>
                    {{ ucfirst($role) }}
                </span>
                <span class="profil-status {{ $status === 'aktif' ? 'is-active' : 'is-inactive' }}">
                    <i class="{{ $status === 'aktif' ? 'ti ti-circle-check' : 'ti ti-circle-off' }}"></i>
                    {{ ucfirst($status) }}
                </span>
            </div>
        </article>

        <article class="profil-mini-card summary-green">
            <span><i class="ti ti-calendar-plus"></i></span>
            <div>
                <small>Dibuat</small>
                <strong>{{ app_date($createdAt) }}</strong>
            </div>
        </article>

        <article class="profil-mini-card summary-blue">
            <span><i class="ti ti-calendar-cog"></i></span>
            <div>
                <small>Update Terakhir</small>
                <strong>{{ app_date($updatedAt) }}</strong>
            </div>
        </article>
    </section>

    <section class="profil-layout profil-password-only" data-aos="fade-up" data-aos-delay="200">
        <article class="profil-card profil-form-card">
            <div class="profil-card-head">
                <div>
                    <span>Keamanan</span>
                    <h3>Reset Password</h3>
                </div>
                <span class="profil-head-badge">
                    <i class="ti ti-shield-lock"></i>
                    Password
                </span>
            </div>

            @if (isset($errors['current_password']) || isset($errors['password']) || isset($errors['password_confirmation']))
                <div class="profil-alert profil-alert-error">
                    <i class="ti ti-alert-triangle"></i>
                    <span>Password belum valid.</span>
                </div>
            @endif

            <form action="{{ url('/kasir/profil/password') }}" method="POST" class="profil-form" data-profil-form>
                @csrf
                <div class="profil-field">
                    <label for="current_password">Password Saat Ini <span>*</span></label>
                    <div class="profil-input-wrap">
                        <i class="ti ti-lock"></i>
                        <input type="password" id="current_password" name="current_password"
                               placeholder="Masukkan password saat ini"
                               class="{{ $fieldErr('current_password') }}"
                               autocomplete="current-password" autofocus>
                        <button type="button" class="profil-toggle-password" data-toggle-password aria-label="Tampilkan password">
                            <i class="ti ti-eye"></i>
                        </button>
                    </div>
                    @if (isset($errors['current_password']))
                        <small class="profil-field-error">{{ $errors['current_password'] }}</small>
                    @endif
                </div>

                <div class="profil-field">
                    <label for="password">Password Baru <span>*</span></label>
                    <div class="profil-input-wrap">
                        <i class="ti ti-key"></i>
                        <input type="password" id="password" name="password"
                               placeholder="Minimal 8 karakter"
                               class="{{ $fieldErr('password') }}"
                               autocomplete="new-password" data-password-input>
                        <button type="button" class="profil-toggle-password" data-toggle-password aria-label="Tampilkan password">
                            <i class="ti ti-eye"></i>
                        </button>
                    </div>
                    <div class="profil-password-meter">
                        <span data-password-meter></span>
                    </div>
                    <small class="profil-field-hint" data-password-hint>Minimal 8 karakter.</small>
                    @if (isset($errors['password']))
                        <small class="profil-field-error">{{ $errors['password'] }}</small>
                    @endif
                </div>

                <div class="profil-field">
                    <label for="password_confirmation">Konfirmasi Password Baru <span>*</span></label>
                    <div class="profil-input-wrap">
                        <i class="ti ti-lock-check"></i>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               placeholder="Ulangi password baru"
                               class="{{ $fieldErr('password_confirmation') }}"
                               autocomplete="new-password">
                        <button type="button" class="profil-toggle-password" data-toggle-password aria-label="Tampilkan password">
                            <i class="ti ti-eye"></i>
                        </button>
                    </div>
                    @if (isset($errors['password_confirmation']))
                        <small class="profil-field-error">{{ $errors['password_confirmation'] }}</small>
                    @endif
                </div>

                <div class="profil-form-actions">
                    <button type="submit" class="profil-btn profil-btn-submit" data-submit-label="Simpan Password">
                        <i class="ti ti-device-floppy"></i>
                        Simpan Password
                    </button>
                </div>
            </form>
        </article>

        <aside class="profil-side">
            <div class="profil-card profil-tips-card">
                <span class="profil-tips-icon"><i class="ti ti-shield-lock"></i></span>
                <h4>Keamanan Akun</h4>
                <ul>
                    <li><i class="ti ti-check"></i> Gunakan password minimal 8 karakter.</li>
                    <li><i class="ti ti-check"></i> Jangan pakai username sebagai password.</li>
                    <li><i class="ti ti-check"></i> Logout kalau komputer dipakai bersama.</li>
                </ul>
            </div>

            <div class="profil-card profil-action-card">
                <h4>Aksi Cepat</h4>
                <a href="{{ url('/kasir/dashboard') }}">
                    <i class="ti ti-layout-dashboard"></i>
                    <span>
                        <strong>Dashboard</strong>
                        <small>Kembali ke ringkasan kasir</small>
                    </span>
                </a>
                <a href="{{ url('/kasir/transaksi') }}">
                    <i class="ti ti-shopping-cart-plus"></i>
                    <span>
                        <strong>POS Transaksi</strong>
                        <small>Mulai transaksi baru</small>
                    </span>
                </a>
            </div>
        </aside>
    </section>
</div>
@endsection
