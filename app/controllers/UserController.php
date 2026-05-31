<?php

declare(strict_types=1);

class UserController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = $this->model('User');
    }

    public function index(): void
    {
        // Cek akses
        $this->requireRole('admin');

        // Pagination
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 10;
        $total = $this->userModel->countAll();
        $totalPages = max(1, (int) ceil($total / $perPage));

        // Ambil data
        $users = $this->userModel->getPaginated($page, $perPage);

        // Tampilkan halaman
        $this->view('admin/user/index', [
            'title' => 'Data User',
            'activeMenu' => 'user',
            'user' => Session::user(),
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
            'flash' => [
                'success' => Session::getFlash('success'),
                'error' => Session::getFlash('error'),
            ],
        ]);
    }

    public function create(): void
    {
        // Cek akses
        $this->requireRole('admin');

        // Tampilkan form
        $this->view('admin/user/form', [
            'title' => 'Tambah Kasir',
            'activeMenu' => 'user',
            'user' => Session::user(),
            'formAction' => '/admin/user/store',
            'formMode' => 'create',
            'userData' => null,
            'errors' => Session::get('_errors', []),
            'old' => Session::get('_old', []),
        ]);

        Session::remove('_errors');
        Session::remove('_old');
    }

    public function store(): void
    {
        // Cek akses
        $this->requireRole('admin');

        // Validasi input
        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => (string) ($_POST['password'] ?? ''),
            'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
            'status' => trim($_POST['status'] ?? 'aktif'),
        ];

        Session::set('_old', [
            'username' => $data['username'],
            'email' => $data['email'],
            'status' => $data['status'],
        ]);

        $errors = Validator::validate($data, [
            'username' => ['required', 'max:50'],
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'min:8'],
            'password_confirmation' => ['required', 'same:password'],
        ]);

        if (!Validator::in($data['status'], ['aktif', 'nonaktif'])) {
            $errors['status'] = 'Status tidak valid.';
        }

        if ($this->userModel->usernameExists($data['username'])) {
            $errors['username'] = 'Username sudah dipakai.';
        }

        if ($this->userModel->emailExists($data['email'])) {
            $errors['email'] = 'Email sudah dipakai.';
        }

        if (!empty($errors)) {
            Session::set('_errors', $errors);
            $this->redirect('/admin/user/create');
        }

        // Simpan kasir
        $createdId = $this->userModel->createKasir($data);

        if ($createdId <= 0) {
            Session::setFlash('error', 'Kasir gagal ditambahkan.');
            $this->redirect('/admin/user/create');
        }

        Session::remove('_old');
        Session::setFlash('success', 'Kasir berhasil ditambahkan.');
        $this->redirect('/admin/user');
    }

    public function edit($id): void
    {
        // Cek akses
        $this->requireRole('admin');

        $id = (int) $id;
        $userData = $this->userModel->findById($id);

        if (!$userData) {
            Session::setFlash('error', 'User tidak ditemukan.');
            $this->redirect('/admin/user');
        }

        if ($userData['role'] === 'admin' || (int) $userData['is_protected'] === 1) {
            Session::setFlash('error', 'Admin utama tidak boleh diedit dari menu ini.');
            $this->redirect('/admin/user');
        }

        // Tampilkan form
        $this->view('admin/user/form', [
            'title' => 'Edit Kasir',
            'activeMenu' => 'user',
            'user' => Session::user(),
            'formAction' => '/admin/user/update/' . $id,
            'formMode' => 'edit',
            'userData' => $userData,
            'errors' => Session::get('_errors', []),
            'old' => Session::get('_old', []),
        ]);

        Session::remove('_errors');
        Session::remove('_old');
    }

    public function update($id): void
    {
        // Cek akses
        $this->requireRole('admin');

        $id = (int) $id;
        $userData = $this->userModel->findById($id);

        if (!$userData) {
            Session::setFlash('error', 'User tidak ditemukan.');
            $this->redirect('/admin/user');
        }

        if ($userData['role'] === 'admin' || (int) $userData['is_protected'] === 1) {
            Session::setFlash('error', 'Admin utama tidak boleh diubah.');
            $this->redirect('/admin/user');
        }

        // Validasi input
        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'status' => trim($_POST['status'] ?? 'aktif'),
        ];

        Session::set('_old', $data);

        $errors = Validator::validate($data, [
            'username' => ['required', 'max:50'],
            'email' => ['required', 'email', 'max:100'],
        ]);

        if (!Validator::in($data['status'], ['aktif', 'nonaktif'])) {
            $errors['status'] = 'Status tidak valid.';
        }

        if ($this->userModel->usernameExists($data['username'], $id)) {
            $errors['username'] = 'Username sudah dipakai.';
        }

        if ($this->userModel->emailExists($data['email'], $id)) {
            $errors['email'] = 'Email sudah dipakai.';
        }

        if (!empty($errors)) {
            Session::set('_errors', $errors);
            $this->redirect('/admin/user/edit/' . $id);
        }

        // Simpan perubahan
        $updated = $this->userModel->updateKasir($id, $data);

        if (!$updated) {
            Session::setFlash('error', 'Kasir gagal diperbarui.');
            $this->redirect('/admin/user/edit/' . $id);
        }

        Session::remove('_old');
        Session::setFlash('success', 'Kasir berhasil diperbarui.');
        $this->redirect('/admin/user');
    }

    public function resetPassword($id): void
    {
        // Cek akses
        $this->requireRole('admin');

        $id = (int) $id;
        $userData = $this->userModel->findById($id);

        if (!$userData) {
            Session::setFlash('error', 'User tidak ditemukan.');
            $this->redirect('/admin/user');
        }

        if ($userData['role'] === 'admin' || (int) $userData['is_protected'] === 1) {
            Session::setFlash('error', 'Password admin utama tidak boleh direset dari menu ini.');
            $this->redirect('/admin/user');
        }

        // Tampilkan form reset
        $this->view('admin/user/reset-password', [
            'title' => 'Reset Password Kasir',
            'activeMenu' => 'user',
            'user' => Session::user(),
            'userData' => $userData,
            'errors' => Session::get('_errors', []),
        ]);

        Session::remove('_errors');
    }

    public function updatePassword($id): void
    {
        // Cek akses
        $this->requireRole('admin');

        $id = (int) $id;
        $userData = $this->userModel->findById($id);

        if (!$userData) {
            Session::setFlash('error', 'User tidak ditemukan.');
            $this->redirect('/admin/user');
        }

        if ($userData['role'] === 'admin' || (int) $userData['is_protected'] === 1) {
            Session::setFlash('error', 'Password admin utama tidak boleh direset dari menu ini.');
            $this->redirect('/admin/user');
        }

        // Validasi input
        $data = [
            'password' => (string) ($_POST['password'] ?? ''),
            'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
        ];

        $errors = Validator::validate($data, [
            'password' => ['required', 'min:8'],
            'password_confirmation' => ['required', 'same:password'],
        ]);

        if (!empty($errors)) {
            Session::set('_errors', $errors);
            $this->redirect('/admin/user/reset-password/' . $id);
        }

        // Simpan password
        $updated = $this->userModel->resetPassword($id, $data['password']);

        if (!$updated) {
            Session::setFlash('error', 'Password kasir gagal direset.');
            $this->redirect('/admin/user/reset-password/' . $id);
        }

        Session::setFlash('success', 'Password kasir berhasil direset.');
        $this->redirect('/admin/user');
    }

    public function delete($id): void
    {
        // Cek akses
        $this->requireRole('admin');

        $id = (int) $id;
        $userData = $this->userModel->findById($id);

        if (!$userData) {
            Session::setFlash('error', 'User tidak ditemukan.');
            $this->redirect('/admin/user');
        }

        if ($userData['role'] === 'admin' || (int) $userData['is_protected'] === 1) {
            Session::setFlash('error', 'Admin utama tidak boleh dihapus.');
            $this->redirect('/admin/user');
        }

        // Cek relasi transaksi
        if ($this->userModel->hasTransactions($id)) {
            Session::setFlash('error', 'Kasir memiliki histori transaksi. Gunakan toggle aktif/nonaktif.');
            $this->redirect('/admin/user');
        }

        // Nonaktifkan kasir
        $deleted = $this->userModel->deleteOrDeactivate($id);

        if (!$deleted) {
            Session::setFlash('error', 'Kasir gagal dinonaktifkan.');
            $this->redirect('/admin/user');
        }

        Session::setFlash('success', 'Kasir berhasil dinonaktifkan.');
        $this->redirect('/admin/user');
    }

    public function toggleStatus($id): void
    {
        $this->requireRole('admin');

        $id = (int) $id;
        $userData = $this->userModel->findById($id);

        if (!$userData) {
            Session::setFlash('error', 'User tidak ditemukan.');
            $this->redirect('/admin/user');
        }

        if ($userData['role'] === 'admin' || (int) $userData['is_protected'] === 1) {
            Session::setFlash('error', 'Admin utama tidak boleh diubah statusnya.');
            $this->redirect('/admin/user');
        }

        $currentStatus = strtolower((string) ($userData['status'] ?? 'aktif'));
        $newStatus = $currentStatus === 'aktif' ? 'nonaktif' : 'aktif';

        // Update status directly
        $updated = $this->userModel->updateKasir($id, [
            'username' => $userData['username'],
            'email' => $userData['email'],
            'status' => $newStatus,
        ]);

        if (!$updated) {
            Session::setFlash('error', 'Gagal mengubah status kasir.');
            $this->redirect('/admin/user');
        }

        $label = $newStatus === 'aktif' ? 'diaktifkan' : 'dinonaktifkan';
        Session::setFlash('success', 'Kasir berhasil ' . $label . '.');
        $this->redirect('/admin/user');
    }
}