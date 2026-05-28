<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Custom Helpers (Kopsis POS)
|--------------------------------------------------------------------------
|
| Helper untuk view (Blade) yang dipakai layout, sidebar, navbar, dll.
| Sengaja dibikin function global biar mirip native lama dan view minim diff.
|
*/

if (! function_exists('app_e')) {
    function app_e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        return url($path);
    }
}

if (! function_exists('app_asset')) {
    function app_asset(string $path): string
    {
        return asset($path);
    }
}

if (! function_exists('app_asset_versioned')) {
    /**
     * Asset URL + cache buster (?v=filemtime). Kalau file berubah, browser fetch ulang.
     */
    function app_asset_versioned(string $path): string
    {
        $cleanPath = strtok($path, '?');
        $diskPath = public_path((string) $cleanPath);

        $version = '1';

        if (is_file($diskPath)) {
            $mtime = filemtime($diskPath);
            if ($mtime !== false) {
                $version = (string) $mtime;
            }
        }

        return asset((string) $cleanPath).'?v='.$version;
    }
}

if (! function_exists('app_user_name')) {
    function app_user_name(mixed $user): string
    {
        if (! $user) {
            return 'Pengguna';
        }

        if (is_array($user)) {
            return $user['nama'] ?? $user['username'] ?? 'Pengguna';
        }

        return $user->nama
            ?? $user->username
            ?? 'Pengguna';
    }
}

if (! function_exists('app_user_role')) {
    function app_user_role(mixed $user): string
    {
        if (! $user) {
            return 'admin';
        }

        $role = is_array($user) ? ($user['role'] ?? 'admin') : ($user->role ?? 'admin');

        return strtolower((string) $role);
    }
}

if (! function_exists('app_user_initial')) {
    function app_user_initial(mixed $user): string
    {
        $name = trim(app_user_name($user));

        if ($name === '') {
            return 'U';
        }

        return strtoupper(mb_substr($name, 0, 1));
    }
}

if (! function_exists('app_is_active')) {
    function app_is_active(string $key, string $activeMenu): string
    {
        return $key === $activeMenu ? 'is-active' : '';
    }
}

if (! function_exists('app_rupiah')) {
    function app_rupiah(mixed $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}

if (! function_exists('app_date')) {
    function app_date(mixed $value, string $format = 'd M Y, H:i'): string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return '-';
        }

        $time = strtotime($raw);

        if ($time === false) {
            return $raw;
        }

        return date($format, $time);
    }
}

if (! function_exists('app_short_text')) {
    function app_short_text(mixed $value, int $limit = 90): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return '-';
        }

        return mb_strlen($text) > $limit
            ? mb_substr($text, 0, $limit).'...'
            : $text;
    }
}

if (! function_exists('current_user_array')) {
    /**
     * Ambil user login sebagai array (kompat sama view native).
     */
    function current_user_array(): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'username' => $user->username,
            'nama' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'is_protected' => $user->is_protected,
            'status' => $user->status,
        ];
    }
}
