@php
    $sidebarUser = $user ?? $currentUser ?? null;
    $sidebarRole = app_user_role($sidebarUser);
    $activeMenu = $activeMenu ?? '';
    $isKasir = $sidebarRole === 'kasir';

    $menus = $isKasir
        ? [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ti ti-layout-dashboard', 'url' => '/kasir/dashboard'],
            ['key' => 'transaksi', 'label' => 'Transaksi', 'icon' => 'ti ti-shopping-cart', 'url' => '/kasir/transaksi'],
            ['key' => 'profil', 'label' => 'Profil Saya', 'icon' => 'ti ti-user-circle', 'url' => '/kasir/profil'],
        ]
        : [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ti ti-layout-dashboard', 'url' => '/admin/dashboard'],
            ['key' => 'kategori', 'label' => 'Kategori', 'icon' => 'ti ti-folder', 'url' => '/admin/kategori'],
            ['key' => 'barang', 'label' => 'Barang', 'icon' => 'ti ti-package', 'url' => '/admin/barang'],
            ['key' => 'supplier', 'label' => 'Supplier', 'icon' => 'ti ti-truck-delivery', 'url' => '/admin/supplier'],
            ['key' => 'restock', 'label' => 'Restock', 'icon' => 'ti ti-stack-push', 'url' => '/admin/restock'],
            ['key' => 'transaksi', 'label' => 'Transaksi', 'icon' => 'ti ti-shopping-cart', 'url' => '/admin/transaksi'],
            ['key' => 'riwayat', 'label' => 'Riwayat Transaksi', 'icon' => 'ti ti-history', 'url' => '/admin/riwayat-transaksi'],
            ['key' => 'laporan', 'label' => 'Laporan', 'icon' => 'ti ti-chart-bar', 'url' => '/admin/laporan'],
            ['key' => 'user', 'label' => 'User Kasir', 'icon' => 'ti ti-users', 'url' => '/admin/user'],
        ];
@endphp

<aside class="app-sidebar" id="appSidebar">
    <div class="app-sidebar-brand">
        <a href="{{ url($isKasir ? '/kasir/dashboard' : '/admin/dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo">
                <img
                    src="{{ asset('assets/images/mts.png') }}"
                    alt="Logo"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                >
                <span class="app-brand-fallback">
                    <i class="ti ti-school"></i>
                </span>
            </span>

            <span class="app-brand-text">
                <strong>Laboratorium</strong>
                <small>Kewirausahaan</small>
            </span>
        </a>

        <button type="button" class="app-sidebar-close" data-sidebar-close aria-label="Tutup sidebar">
            <i class="ti ti-x"></i>
        </button>
    </div>

    <nav class="app-sidebar-nav" aria-label="Navigasi utama">
        @foreach ($menus as $menu)
            <a
                href="{{ url($menu['url']) }}"
                class="app-sidebar-link {{ app_is_active($menu['key'], $activeMenu) }}"
            >
                <span class="app-sidebar-icon">
                    <i class="{{ $menu['icon'] }}"></i>
                </span>
                <span>{{ $menu['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="app-sidebar-footer">
        <div class="app-sidebar-user">
            <span class="app-user-avatar">
                {{ app_user_initial($sidebarUser) }}
            </span>

            <span class="app-user-meta">
                <strong>{{ app_user_name($sidebarUser) }}</strong>
                <small>{{ ucfirst($sidebarRole ?: 'User') }}</small>
            </span>
        </div>

        <form action="{{ url('/logout') }}" method="POST" class="app-logout-form">
            @csrf
            <button type="submit" class="app-sidebar-link app-sidebar-logout">
                <span class="app-sidebar-icon">
                    <i class="ti ti-logout"></i>
                </span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

<div class="app-sidebar-backdrop" data-sidebar-close></div>
