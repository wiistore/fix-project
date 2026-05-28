@php
    $pageCss = ['assets/css/user.css'];
    $pageScripts = ['assets/js/user.js'];

    $users = $users ?? [];
    $flash = $flash ?? [];
    $success = $flash['success'] ?? null;
    $error = $flash['error'] ?? null;

    $totalUser = count($users);
    $totalAdmin = 0;
    $totalKasir = 0;
    $totalAktif = 0;
    $totalNonaktif = 0;
    $totalProtected = 0;

    foreach ($users as $u) {
        $r = strtolower((string) ($u['role'] ?? ''));
        $s = strtolower((string) ($u['status'] ?? ''));
        if ($r === 'admin') $totalAdmin++;
        if ($r === 'kasir') $totalKasir++;
        if ($s === 'aktif') $totalAktif++;
        if ($s === 'nonaktif') $totalNonaktif++;
        if ((int) ($u['is_protected'] ?? 0) === 1) $totalProtected++;
    }

    $summaryCards = [
        ['class' => 'summary-green', 'icon' => 'ti ti-users', 'label' => 'Total User', 'value' => $totalUser, 'desc' => 'Admin dan kasir'],
        ['class' => 'summary-blue', 'icon' => 'ti ti-user-check', 'label' => 'Kasir', 'value' => $totalKasir, 'desc' => 'Akun operasional'],
        ['class' => 'summary-orange', 'icon' => 'ti ti-circle-check', 'label' => 'User Aktif', 'value' => $totalAktif, 'desc' => 'Bisa login'],
        ['class' => 'summary-purple', 'icon' => 'ti ti-shield-lock', 'label' => 'Protected', 'value' => $totalProtected, 'desc' => 'Tidak bisa diedit'],
    ];
@endphp

@extends('layouts.app', [
    'title' => $title ?? 'Data User Kasir',
    'activeMenu' => $activeMenu ?? 'user',
    'pageCss' => $pageCss,
    'pageScripts' => $pageScripts,
])

@section('content')
<div class="user-page">
    @if ($success)
        <div class="user-alert user-alert-success">
            <i class="ti ti-circle-check"></i>
            <span>{{ $success }}</span>
        </div>
    @endif

    @if ($error)
        <div class="user-alert user-alert-error">
            <i class="ti ti-alert-triangle"></i>
            <span>{{ $error }}</span>
        </div>
    @endif

    <section class="user-hero" data-aos="fade-down" data-aos-duration="700">
        <div class="user-hero-content">
            <span class="user-eyebrow">
                <i class="ti ti-users"></i>
                Manajemen User
            </span>
            <h2>User Kasir</h2>
        </div>

        <div class="user-hero-actions">
            <a href="{{ url('/admin/user/create') }}" class="user-btn user-btn-primary">
                <i class="ti ti-user-plus"></i>
                Tambah Kasir
            </a>
            <a href="{{ url('/admin/dashboard') }}" class="user-btn user-btn-soft">
                <i class="ti ti-layout-dashboard"></i>
                Dashboard
            </a>
        </div>
    </section>

    <section class="user-summary summary-count-4" data-aos="fade-up" data-aos-delay="140">
        @foreach ($summaryCards as $idx => $card)
            <article class="user-summary-card {{ $card['class'] }}" data-aos="zoom-in" data-aos-delay="{{ 80 + ($idx * 100) }}">
                <span class="user-summary-icon"><i class="{{ $card['icon'] }}"></i></span>
                <div>
                    <small>{{ $card['label'] }}</small>
                    <strong>{{ $card['value'] }}</strong>
                    <p>{{ $card['desc'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="user-panel" data-aos="fade-up" data-aos-delay="200">
        <div class="user-panel-header">
            <div>
                <span>Akun</span>
                <h3>Daftar User</h3>
            </div>

            <div class="user-tools">
                <label class="user-search">
                    <i class="ti ti-search"></i>
                    <input type="search" placeholder="Cari username, email, role..." data-user-search>
                </label>

                <select class="user-filter" data-user-role-filter aria-label="Filter role">
                    <option value="">Semua Role</option>
                    <option value="admin">Admin</option>
                    <option value="kasir">Kasir</option>
                </select>

                <select class="user-filter" data-user-status-filter aria-label="Filter status">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>

                <button type="button" class="user-btn user-btn-ghost" data-user-reset>
                    <i class="ti ti-refresh"></i> Reset
                </button>
            </div>
        </div>

        @if (empty($users))
            <div class="user-empty">
                <span><i class="ti ti-user-off"></i></span>
                <h4>Belum ada user</h4>
                <p>Tambahkan kasir dulu supaya POS punya operator.</p>
                <a href="{{ url('/admin/user/create') }}" class="user-btn user-btn-form">
                    <i class="ti ti-user-plus"></i> Tambah Kasir
                </a>
            </div>
        @else
            <div class="user-table-wrap">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Proteksi</th>
                            <th>Dibuat</th>
                            <th>Update</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody data-user-table-body>
                        @foreach ($users as $index => $u)
                            @php
                                $id = (int) ($u['id'] ?? 0);
                                $username = (string) ($u['username'] ?? '-');
                                $email = (string) ($u['email'] ?? '-');
                                $role = strtolower((string) ($u['role'] ?? '-'));
                                $status = strtolower((string) ($u['status'] ?? 'nonaktif'));
                                $isProtected = $role === 'admin' || (int) ($u['is_protected'] ?? 0) === 1;
                                $searchText = strtolower(implode(' ', [$username, $email, $role, $status]));
                            @endphp

                            <tr data-user-row data-search="{{ $searchText }}" data-role="{{ $role }}" data-status="{{ $status }}">
                                <td><span class="user-number">{{ $index + 1 }}</span></td>

                                <td>
                                    <div class="user-name">
                                        <span class="user-avatar {{ $role === 'admin' ? 'is-admin' : '' }}">
                                            <i class="{{ $role === 'admin' ? 'ti ti-shield-lock' : 'ti ti-user' }}"></i>
                                        </span>
                                        <div>
                                            <strong>{{ $username }}</strong>
                                            <small>ID: {{ $id }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <a href="mailto:{{ $email }}" class="user-email">
                                        <i class="ti ti-mail"></i>
                                        {{ $email }}
                                    </a>
                                </td>

                                <td>
                                    <span class="user-role role-{{ $role }}">
                                        <i class="{{ $role === 'admin' ? 'ti ti-crown' : 'ti ti-cash-register' }}"></i>
                                        {{ ucfirst($role) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="user-status {{ $status === 'aktif' ? 'status-active' : 'status-inactive' }}">
                                        <i class="{{ $status === 'aktif' ? 'ti ti-circle-check' : 'ti ti-circle-off' }}"></i>
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="user-protected {{ $isProtected ? 'is-locked' : 'is-open' }}">
                                        <i class="{{ $isProtected ? 'ti ti-lock' : 'ti ti-lock-open' }}"></i>
                                        {{ $isProtected ? 'Dilindungi' : 'Bisa dikelola' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="user-date">
                                        <i class="ti ti-calendar-plus"></i>
                                        {{ app_date($u['created_at'] ?? '') }}
                                    </span>
                                </td>

                                <td>
                                    <span class="user-date">
                                        <i class="ti ti-calendar-cog"></i>
                                        {{ app_date($u['updated_at'] ?? '') }}
                                    </span>
                                </td>

                                <td>
                                    <div class="user-actions">
                                        @if (! $isProtected)
                                            @php
                                                $nextStatus = $status === 'aktif' ? 'nonaktif' : 'aktif';
                                                $toggleTitle = $status === 'aktif' ? 'Nonaktifkan Kasir' : 'Aktifkan Kasir';
                                                $toggleMessage = $status === 'aktif'
                                                    ? 'Kasir '.$username.' akan dinonaktifkan. Histori transaksi tetap aman.'
                                                    : 'Kasir '.$username.' akan diaktifkan lagi.';
                                                $toggleBtnClass = $status === 'aktif' ? 'action-delete' : 'action-activate';
                                                $toggleIcon = $status === 'aktif' ? 'ti ti-user-off' : 'ti ti-user-check';
                                                $toggleLabel = $status === 'aktif' ? 'Nonaktifkan kasir' : 'Aktifkan kasir';
                                            @endphp

                                            <a href="{{ url('/admin/user/edit/'.$id) }}"
                                               class="user-action-btn action-edit"
                                               title="Edit kasir">
                                                <i class="ti ti-edit"></i>
                                            </a>

                                            <a href="{{ url('/admin/user/reset-password/'.$id) }}"
                                               class="user-action-btn action-password"
                                               title="Reset password">
                                                <i class="ti ti-key"></i>
                                            </a>

                                            <form action="{{ url('/admin/user/update/'.$id) }}"
                                                  method="POST"
                                                  data-user-delete-form
                                                  data-confirm-title="{{ $toggleTitle }}"
                                                  data-confirm-message="{{ $toggleMessage }}"
                                                  data-confirm-submit="{{ $status === 'aktif' ? 'Ya, nonaktifkan' : 'Ya, aktifkan' }}">
                                                @csrf
                                                <input type="hidden" name="username" value="{{ $username }}">
                                                <input type="hidden" name="email" value="{{ $email }}">
                                                <input type="hidden" name="status" value="{{ $nextStatus }}">

                                                <button type="submit"
                                                        class="user-action-btn {{ $toggleBtnClass }}"
                                                        title="{{ $toggleLabel }}"
                                                        aria-label="{{ $toggleLabel }}">
                                                    <i class="{{ $toggleIcon }}"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="user-action-disabled" title="Admin/protected tidak bisa diedit">
                                                <i class="ti ti-lock"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="user-filter-empty" data-user-filter-empty hidden>
                    <span><i class="ti ti-search-off"></i></span>
                    <h4>User tidak ketemu</h4>
                    <p>Keyword atau filter terlalu sempit.</p>
                </div>
            </div>

            @include('components.pagination', ['pagination' => $pagination ?? null])
        @endif
    </section>
</div>
@endsection
