<?php
$currentUser = $currentUser ?? ($user ?? (class_exists('Session') ? Session::user() : null));
$currentRole = app_user_role($currentUser);
$title = $title ?? 'Dashboard';
?>

<main class="app-main">
    <header class="app-navbar">
        <div class="app-navbar-left">
            <button type="button" class="app-icon-button app-sidebar-toggle" data-sidebar-toggle aria-label="Buka sidebar">
                <i class="ti ti-menu-2"></i>
            </button>

            <div class="app-page-title">
                <span><?= app_e(ucfirst($currentRole ?: 'User')) ?></span>
                <h1><?= app_e($title) ?></h1>
            </div>
        </div>

        <div class="app-navbar-right">
            <div class="app-navbar-search" data-global-search-wrap>
                <i class="ti ti-search"></i>
                <input type="search" placeholder="Cari menu..." data-global-search autocomplete="off">
                <div class="app-search-results" data-global-search-results hidden></div>
            </div>

            <div class="app-profile" data-profile-menu>
                <button type="button" class="app-profile-button" data-profile-toggle aria-label="Menu profil">
                    <span class="app-user-avatar">
                        <?= app_e(app_user_initial($currentUser)) ?>
                    </span>

                    <span class="app-profile-text">
                        <strong><?= app_e(app_user_name($currentUser)) ?></strong>
                        <small><?= app_e(ucfirst($currentRole ?: 'User')) ?></small>
                    </span>

                    <i class="ti ti-chevron-down"></i>
                </button>

                <div class="app-profile-dropdown">
                    <div class="app-profile-dropdown-head">
                        <span class="app-user-avatar">
                            <?= app_e(app_user_initial($currentUser)) ?>
                        </span>

                        <div>
                            <strong><?= app_e(app_user_name($currentUser)) ?></strong>
                            <small><?= app_e(ucfirst($currentRole ?: 'User')) ?></small>
                        </div>
                    </div>

                    <?php if ($currentRole === 'kasir'): ?>
                        <a href="<?= app_e(app_url('/kasir/profil')) ?>">
                            <i class="ti ti-user-circle"></i>
                            Profil Saya
                        </a>
                    <?php endif; ?>

                    <form action="<?= app_e(app_url('/logout')) ?>" method="POST">
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