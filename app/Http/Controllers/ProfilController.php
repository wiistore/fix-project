<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfilController extends Controller
{
    public function index(Request $request)
    {
        $user = User::find(auth()->id());

        if (! $user) {
            auth()->logout();

            return redirect('/login');
        }

        return view('kasir.profil', [
            'title' => 'Profil Saya',
            'activeMenu' => 'profil',
            'user' => current_user_array(),
            'userData' => $user->toArray(),
            'flash' => [
                'error' => $request->session()->get('error'),
                'success' => $request->session()->get('success'),
            ],
            'errors' => $request->session()->get('errors_kv', []),
            'old' => $request->session()->getOldInput() ?: [],
        ]);
    }

    public function update(Request $request)
    {
        $userId = (int) auth()->id();

        $data = [
            'username' => trim((string) $request->input('username', '')),
            'email' => trim((string) $request->input('email', '')),
        ];

        $errors = [];

        if ($data['username'] === '') {
            $errors['username'] = 'Username wajib diisi.';
        } elseif (mb_strlen($data['username']) > 50) {
            $errors['username'] = 'Username maksimal 50 karakter.';
        }

        if ($data['email'] === '') {
            $errors['email'] = 'Email wajib diisi.';
        } elseif (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email harus berupa email valid.';
        } elseif (mb_strlen($data['email']) > 100) {
            $errors['email'] = 'Email maksimal 100 karakter.';
        }

        if (empty($errors['username']) && User::where('username', $data['username'])->where('id', '!=', $userId)->exists()) {
            $errors['username'] = 'Username sudah dipakai.';
        }

        if (empty($errors['email']) && User::where('email', $data['email'])->where('id', '!=', $userId)->exists()) {
            $errors['email'] = 'Email sudah dipakai.';
        }

        if (! empty($errors)) {
            return redirect('/kasir/profil')
                ->withInput($data)
                ->with('errors_kv', $errors);
        }

        User::where('id', $userId)->update($data);

        return redirect('/kasir/profil')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $userId = (int) auth()->id();

        $current = (string) $request->input('current_password', '');
        $password = (string) $request->input('password', '');
        $confirm = (string) $request->input('password_confirmation', '');

        $errors = [];

        if ($current === '') {
            $errors['current_password'] = 'Password saat ini wajib diisi.';
        }

        if ($password === '') {
            $errors['password'] = 'Password wajib diisi.';
        } elseif (mb_strlen($password) < 8) {
            $errors['password'] = 'Password minimal 8 karakter.';
        }

        if ($confirm === '') {
            $errors['password_confirmation'] = 'Konfirmasi password wajib diisi.';
        } elseif ($confirm !== $password) {
            $errors['password_confirmation'] = 'Konfirmasi password tidak sama.';
        }

        $user = User::find($userId);

        if (empty($errors['current_password']) && $user && ! Hash::check($current, $user->password)) {
            $errors['current_password'] = 'Password saat ini salah.';
        }

        if (! empty($errors)) {
            return redirect('/kasir/profil')->with('errors_kv', $errors);
        }

        $user->password = Hash::make($password);
        $user->save();

        return redirect('/kasir/profil')->with('success', 'Password berhasil diperbarui.');
    }
}
