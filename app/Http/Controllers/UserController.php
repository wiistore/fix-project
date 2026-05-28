<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;
        $total = User::count();
        $totalPages = max(1, (int) ceil($total / $perPage));

        $users = User::orderByRaw("role = 'admin' DESC")
            ->orderBy('id')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn ($u) => $u->toArray() + ['nama' => $u->username])
            ->all();

        return view('admin.user.index', [
            'title' => 'Data User',
            'activeMenu' => 'user',
            'user' => current_user_array(),
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.user.form', [
            'title' => 'Tambah Kasir',
            'activeMenu' => 'user',
            'user' => current_user_array(),
            'formAction' => '/admin/user/store',
            'formMode' => 'create',
            'userData' => null,
            'errors' => $request->session()->get('errors_kv', []),
            'old' => $request->session()->getOldInput() ?: [],
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'username' => trim((string) $request->input('username', '')),
            'email' => trim((string) $request->input('email', '')),
            'password' => (string) $request->input('password', ''),
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
            'status' => trim((string) $request->input('status', 'aktif')),
        ];

        $errors = $this->validatePayload($data, true);

        if (User::where('username', $data['username'])->exists()) {
            $errors['username'] = 'Username sudah dipakai.';
        }

        if (User::where('email', $data['email'])->exists()) {
            $errors['email'] = 'Email sudah dipakai.';
        }

        if (! empty($errors)) {
            return redirect('/admin/user/create')
                ->withInput([
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'status' => $data['status'],
                ])
                ->with('errors_kv', $errors);
        }

        User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'kasir',
            'is_protected' => false,
            'status' => $data['status'],
        ]);

        return redirect('/admin/user')->with('success', 'Kasir berhasil ditambahkan.');
    }

    public function edit(Request $request, $id)
    {
        $user = User::find((int) $id);

        if (! $user) {
            return redirect('/admin/user')->with('error', 'User tidak ditemukan.');
        }

        if ($user->role === 'admin' || $user->is_protected) {
            return redirect('/admin/user')->with('error', 'Admin utama tidak boleh diedit dari menu ini.');
        }

        return view('admin.user.form', [
            'title' => 'Edit Kasir',
            'activeMenu' => 'user',
            'user' => current_user_array(),
            'formAction' => '/admin/user/update/'.$id,
            'formMode' => 'edit',
            'userData' => $user->toArray(),
            'errors' => $request->session()->get('errors_kv', []),
            'old' => $request->session()->getOldInput() ?: [],
        ]);
    }

    public function update(Request $request, $id)
    {
        $id = (int) $id;
        $user = User::find($id);

        if (! $user) {
            return redirect('/admin/user')->with('error', 'User tidak ditemukan.');
        }

        if ($user->role === 'admin' || $user->is_protected) {
            return redirect('/admin/user')->with('error', 'Admin utama tidak boleh diubah.');
        }

        $data = [
            'username' => trim((string) $request->input('username', '')),
            'email' => trim((string) $request->input('email', '')),
            'status' => trim((string) $request->input('status', 'aktif')),
        ];

        $errors = $this->validatePayload($data, false);

        if (User::where('username', $data['username'])->where('id', '!=', $id)->exists()) {
            $errors['username'] = 'Username sudah dipakai.';
        }

        if (User::where('email', $data['email'])->where('id', '!=', $id)->exists()) {
            $errors['email'] = 'Email sudah dipakai.';
        }

        if (! empty($errors)) {
            return redirect('/admin/user/edit/'.$id)
                ->withInput($data)
                ->with('errors_kv', $errors);
        }

        $user->update($data);

        return redirect('/admin/user')->with('success', 'Kasir berhasil diperbarui.');
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::find((int) $id);

        if (! $user) {
            return redirect('/admin/user')->with('error', 'User tidak ditemukan.');
        }

        if ($user->role === 'admin' || $user->is_protected) {
            return redirect('/admin/user')->with('error', 'Password admin utama tidak boleh direset dari sini.');
        }

        return view('admin.user.reset-password', [
            'title' => 'Reset Password Kasir',
            'activeMenu' => 'user',
            'user' => current_user_array(),
            'userData' => $user->toArray(),
            'errors' => $request->session()->get('errors_kv', []),
        ]);
    }

    public function updatePassword(Request $request, $id)
    {
        $id = (int) $id;
        $user = User::find($id);

        if (! $user) {
            return redirect('/admin/user')->with('error', 'User tidak ditemukan.');
        }

        if ($user->role === 'admin' || $user->is_protected) {
            return redirect('/admin/user')->with('error', 'Admin utama tidak boleh direset dari sini.');
        }

        $password = (string) $request->input('password', '');
        $confirm = (string) $request->input('password_confirmation', '');

        $errors = [];

        if ($password === '') {
            $errors['password'] = 'Password wajib diisi.';
        } elseif (mb_strlen($password) < 8) {
            $errors['password'] = 'Password minimal 8 karakter.';
        }

        if ($confirm !== $password) {
            $errors['password_confirmation'] = 'Konfirmasi password tidak sama.';
        }

        if (! empty($errors)) {
            return redirect('/admin/user/reset-password/'.$id)->with('errors_kv', $errors);
        }

        $user->password = Hash::make($password);
        $user->save();

        return redirect('/admin/user')->with('success', 'Password kasir berhasil direset.');
    }

    public function delete($id)
    {
        $id = (int) $id;
        $user = User::find($id);

        if (! $user) {
            return redirect('/admin/user')->with('error', 'User tidak ditemukan.');
        }

        if ($user->role === 'admin' || $user->is_protected) {
            return redirect('/admin/user')->with('error', 'Admin utama tidak boleh dihapus.');
        }

        // Cek histori transaksi
        if (DB::table('transaksi')->where('id_user', $id)->exists()) {
            $user->update(['status' => 'nonaktif']);

            return redirect('/admin/user')->with('success', 'Kasir punya histori, dinonaktifkan.');
        }

        $user->update(['status' => 'nonaktif']);

        return redirect('/admin/user')->with('success', 'Kasir berhasil dinonaktifkan.');
    }

    public function toggleStatus($id)
    {
        $id = (int) $id;
        $user = User::find($id);

        if (! $user) {
            return redirect('/admin/user')->with('error', 'User tidak ditemukan.');
        }

        if ($user->role === 'admin' || $user->is_protected) {
            return redirect('/admin/user')->with('error', 'Admin utama tidak boleh diubah statusnya.');
        }

        $newStatus = $user->status === 'aktif' ? 'nonaktif' : 'aktif';
        $user->update(['status' => $newStatus]);

        $label = $newStatus === 'aktif' ? 'diaktifkan' : 'dinonaktifkan';

        return redirect('/admin/user')->with('success', 'Kasir berhasil '.$label.'.');
    }

    private function validatePayload(array $data, bool $withPassword): array
    {
        $errors = [];

        if (($data['username'] ?? '') === '') {
            $errors['username'] = 'Username wajib diisi.';
        } elseif (mb_strlen($data['username']) > 50) {
            $errors['username'] = 'Username maksimal 50 karakter.';
        }

        if (($data['email'] ?? '') === '') {
            $errors['email'] = 'Email wajib diisi.';
        } elseif (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email harus berupa email valid.';
        } elseif (mb_strlen($data['email']) > 100) {
            $errors['email'] = 'Email maksimal 100 karakter.';
        }

        if (! in_array($data['status'] ?? 'aktif', ['aktif', 'nonaktif'], true)) {
            $errors['status'] = 'Status tidak valid.';
        }

        if ($withPassword) {
            if (($data['password'] ?? '') === '') {
                $errors['password'] = 'Password wajib diisi.';
            } elseif (mb_strlen($data['password']) < 8) {
                $errors['password'] = 'Password minimal 8 karakter.';
            }

            if (($data['password_confirmation'] ?? '') !== ($data['password'] ?? '')) {
                $errors['password_confirmation'] = 'Konfirmasi password tidak sama.';
            }
        }

        return $errors;
    }
}
