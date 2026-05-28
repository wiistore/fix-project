@php
    $navUser = $user ?? $currentUser ?? null;
    $navRole = app_user_role($navUser);
    $title = $title ?? 'Dashboard';
@endphp

<main class="app-main">
    <header class="app-navbar">
        <div class="app-navbar-left">
            <button type="button" class="app-icon-button app-sidebar-toggle" data-sidebar-toggle aria-label="Buka sidebar">
                <i class="ti ti-menu-2"></i>
            </button>

            <div class="app-page-title">
                <span>{{ ucfirst($navRole ?: 'User') }}</span>
                <h1>{{ $title }}</h1>
            </div>
        </div>

        <div class="app-navbar-right">
            <div class="app-navbar-search">
                <i class="ti ti-search"></i>
                <input type="search" placeholder="Cari menu..." data-global-search>
            </div>

            <div class="app-profile" data-profile-menu>
                <button type="button" class="app-profile-button" data-profile-toggle aria-label="Menu profil">
                    <span class="app-user-avatar">
                        {{ app_user_initial($navUser) }}
                    </span>

                    <span class="app-profile-text">
                        <strong>{{ app_user_name($navUser) }}</strong>
                        <small>{{ ucfirst($navRole ?: 'User') }}</small>
                    </span>

                    <i class="ti ti-chevron-down"></i>
                </button>

                <div class="app-profile-dropdown">
                    <div class="app-profile-dropdown-head">
                        <span class="app-user-avatar">
                            {{ app_user_initial($navUser) }}
                        </span>

                        <div>
                            <strong>{{ app_user_name($navUser) }}</strong>
                            <small>{{ ucfirst($navRole ?: 'User') }}</small>
                        </div>
                    </div>

                    @if ($navRole === 'kasir')
                        <a href="{{ url('/kasir/profil') }}">
                            <i class="ti ti-user-circle"></i>
                            Profil Saya
                        </a>
                    @endif

                    <form action="{{ url('/logout') }}" method="POST">
                        @csrf
                        <button type="submit">
                            <i class="ti ti-logout"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <section class="app-content">
