<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function loginForm(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.login', [
            'title' => 'Login',
            'flash' => [
                'error' => $request->session()->get('error'),
                'success' => $request->session()->get('success'),
            ],
            'old' => [
                'username' => $request->session()->getOldInput('username', ''),
            ],
        ]);
    }

    public function login(Request $request)
    {
        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');

        $request->flashOnly(['username']);

        if ($username === '' || $password === '') {
            return redirect('/login')->with('error', 'Username dan password wajib diisi.');
        }

        // Cari by username atau email
        $user = User::where('username', $username)
            ->orWhere('email', $username)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return redirect('/login')->with('error', 'Username atau password salah.');
        }

        if ($user->status !== 'aktif') {
            return redirect('/login')->with('error', 'Akun kamu sedang nonaktif.');
        }

        if (! in_array($user->role, ['admin', 'kasir'], true)) {
            return redirect('/login')->with('error', 'Role akun tidak valid.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($user->role);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function redirectByRole(?string $role)
    {
        return match ($role) {
            'admin' => redirect('/admin/dashboard'),
            'kasir' => redirect('/kasir/dashboard'),
            default => redirect('/login'),
        };
    }
}
