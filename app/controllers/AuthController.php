<?php

declare(strict_types=1);

class AuthController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = $this->model('User');
    }

    public function loginForm(): void
    {
        // Kalau sudah login, langsung arahkan sesuai role
        if (Session::isLoggedIn()) {
            $this->redirectByRole(Session::role());
        }

        $this->view('auth/login', [
            'title' => 'Login',
            'flash' => [
                'error' => Session::getFlash('error'),
                'success' => Session::getFlash('success'),
            ],
            'old' => Session::get('_old_login', []),
        ]);

        Session::remove('_old_login');
    }

    public function login(): void
    {
        // Validasi input
        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        Session::set('_old_login', [
            'username' => $username,
        ]);

        if ($username === '' || $password === '') {
            Session::setFlash('error', 'Username dan password wajib diisi.');
            $this->redirect('/login');
        }

        // Cek user
        $user = $this->userModel->findByUsername($username);

        if (!$user || !Security::passwordVerify($password, $user['password'])) {
            Session::setFlash('error', 'Username atau password salah.');
            $this->redirect('/login');
        }

        if (($user['status'] ?? 'nonaktif') !== 'aktif') {
            Session::setFlash('error', 'Akun kamu sedang nonaktif.');
            $this->redirect('/login');
        }

        if (!in_array($user['role'], ['admin', 'kasir'], true)) {
            Session::setFlash('error', 'Role akun tidak valid.');
            $this->redirect('/login');
        }

        // Simpan session
        unset($user['password']);

        $user['nama'] = $user['username'];

        Session::remove('_old_login');
        Session::login($user);

        $this->redirectByRole($user['role']);
    }

    public function logout(): void
    {
        Session::logout();
        Response::redirect('/login');
    }

    private function redirectByRole(?string $role): void
    {
        if ($role === 'admin') {
            $this->redirect('/admin/dashboard');
        }

        if ($role === 'kasir') {
            $this->redirect('/kasir/dashboard');
        }

        Session::logout();
        $this->redirect('/login');
    }
}