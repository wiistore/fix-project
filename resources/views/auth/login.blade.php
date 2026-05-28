@php
    $title = $title ?? 'Login';
    $appName = config('app.name', 'Kopsis POS');
    $flash = $flash ?? [];
    $old = $old ?? [];

    $errorMessage = $flash['error'] ?? session('error');
    $successMessage = $flash['success'] ?? session('success');
    $oldUsername = is_array($old) ? ($old['username'] ?? '') : '';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - {{ $appName }}</title>

    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="{{ asset('assets/css/auth.css') }}">
</head>
<body>
    <main class="auth-page" data-aos-delay="0">
        <canvas id="authParticles" class="auth-particles" aria-hidden="true"></canvas>

        <section class="auth-left" data-aos="fade-right" data-aos-duration="800">
            <div class="auth-shape auth-shape-1"></div>
            <div class="auth-shape auth-shape-2"></div>
            <div class="auth-dots auth-dots-1"></div>
            <div class="auth-dots auth-dots-2"></div>
            <div class="auth-cross"></div>

            <div class="auth-bg-hero" aria-hidden="true">
                <img
                    src="{{ asset('assets/images/icon.png') }}"
                    alt=""
                    onerror="this.parentElement.style.display='none';">
            </div>

            <div class="auth-left-center">
                <div class="auth-logo-wrap" data-aos="zoom-in" data-aos-delay="60">
                    <img
                        src="{{ asset('assets/images/mts.png') }}"
                        alt="Logo Laboratorium Kewirausahaan"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                    >
                    <span class="auth-logo-fallback">
                        <i class="ti ti-school"></i>
                    </span>
                </div>

                <div class="auth-title-block" data-aos="fade-down" data-aos-delay="120">
                    <span>Sistem Kasir</span>
                    <h1>Laboratorium Kewirausahaan</h1>
                    <p>MTSN 8 Banyuwangi</p>
                    <div class="auth-title-line"></div>
                </div>

                <p class="auth-description" data-aos="fade-up" data-aos-delay="180">
                    Kelola Koperasi Sekolah dengan lebih mudah, cepat, dan terstruktur.
                </p>
            </div>
        </section>

        <section class="auth-right" data-aos="fade-left" data-aos-duration="800">
            <div class="auth-form-card" data-aos="fade-left" data-aos-delay="140">
                <div class="auth-lock" data-aos="zoom-in" data-aos-delay="190">
                    <i class="ti ti-lock"></i>
                </div>

                <div class="auth-heading">
                    <h2>LOGIN</h2>
                    <p>Masuk untuk mengakses sistem kasir</p>
                </div>

                @if (! empty($errorMessage))
                    <div class="auth-alert auth-alert-danger" role="alert">
                        <i class="ti ti-alert-circle"></i>
                        <span>{{ $errorMessage }}</span>
                    </div>
                @endif

                @if (! empty($successMessage))
                    <div class="auth-alert auth-alert-success" role="alert">
                        <i class="ti ti-circle-check"></i>
                        <span>{{ $successMessage }}</span>
                    </div>
                @endif

                <form action="{{ url('/login') }}" method="POST" class="auth-form" autocomplete="on">
                    @csrf

                    <div class="auth-field">
                        <label for="username">Username</label>
                        <div class="auth-input">
                            <i class="ti ti-user"></i>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                value="{{ $oldUsername }}"
                                placeholder="Username"
                                autocomplete="username"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="password">Password</label>
                        <div class="auth-input">
                            <i class="ti ti-lock"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Password"
                                autocomplete="current-password"
                                required
                            >
                            <button
                                type="button"
                                class="auth-eye"
                                data-password-toggle="password"
                                aria-label="Tampilkan password"
                            >
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="auth-submit">
                        Masuk ke Sistem
                    </button>
                </form>
            </div>
        </section>
    </main>

    <script src="{{ asset('assets/js/auth-particles.js') }}"></script>
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        (function () {
            if (typeof AOS === 'undefined') return;
            var isMobile = window.matchMedia('(max-width: 768px)').matches;
            AOS.init({
                duration: 600,
                easing: 'ease-out-cubic',
                once: false,
                mirror: false,
                offset: 40,
                disable: function () { return isMobile; }
            });
        })();
    </script>
</body>
</html>
