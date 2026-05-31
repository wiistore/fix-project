<?php

declare(strict_types=1);

class KasirController extends Controller
{
    private $dashboardModel;
    private $userModel;

    public function __construct()
    {
        $this->dashboardModel = $this->model('Dashboard');
        $this->userModel = $this->model('User');
    }

    public function dashboard(): void
    {
        // Cek akses
        $this->requireRole('kasir');

        $user = Session::user();
        $userId = (int) ($user['id'] ?? 0);

        // Ambil data
        $summary = $this->dashboardModel->kasirSummary($userId);

        // Tampilkan halaman
        $this->view('kasir/dashboard', [
            'title' => 'Dashboard Kasir',
            'activeMenu' => 'dashboard',
            'user' => $user,
            'dashboard' => $summary,
            'totalTransaksiHariIni' => $summary['total_transaksi_hari_ini'],
            'totalPenjualanHariIni' => $summary['penjualan_hari_ini'],
            'totalItemHariIni' => $summary['total_item_hari_ini'],
            'transaksiTerbaru' => $summary['transaksi_terbaru'],
        ]);
    }

    public function profil(): void
    {
        // Cek akses
        $this->requireRole('kasir');

        $userId = (int) Session::userId();
        $userData = $this->userModel->findById($userId);

        if (!$userData) {
            Session::logout();
            $this->redirect('/login');
        }

        // Tampilkan halaman
        $this->view('kasir/profil', [
            'title' => 'Profil Saya',
            'activeMenu' => 'profil',
            'user' => Session::user(),
            'userData' => $userData,
            'flash' => [
                'error' => Session::getFlash('error'),
                'success' => Session::getFlash('success'),
            ],
            'errors' => Session::get('_errors', []),
            'old' => Session::get('_old', []),
        ]);

        Session::remove('_errors');
        Session::remove('_old');
    }

    public function updatePassword(): void
    {
        // Cek akses
        $this->requireRole('kasir');

        $userId = (int) Session::userId();

        // Validasi input
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        $errors = Validator::validate([
            'current_password' => $currentPassword,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'current_password' => ['required'],
            'password' => ['required', 'min:8'],
            'password_confirmation' => ['required', 'same:password'],
        ]);

        $user = $this->userModel->findByIdWithPassword($userId);

        if (!$user || !Security::passwordVerify($currentPassword, $user['password'])) {
            $errors['current_password'] = 'Password saat ini salah.';
        }

        if (!empty($errors)) {
            Session::set('_errors', $errors);
            $this->redirect('/kasir/profil');
        }

        // Simpan password
        $updated = $this->userModel->updateOwnPassword($userId, $password);

        if (!$updated) {
            Session::setFlash('error', 'Password gagal diperbarui.');
            $this->redirect('/kasir/profil');
        }

        Session::setFlash('success', 'Password berhasil diperbarui.');
        $this->redirect('/kasir/profil');
    }
}