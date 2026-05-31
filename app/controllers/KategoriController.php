<?php

declare(strict_types=1);

class KategoriController extends Controller
{
    private $kategoriModel;

    public function __construct()
    {
        $this->kategoriModel = $this->model('Kategori');
    }

    public function index(): void
    {
        // Cek akses
        $this->requireRole('admin');

        // Pagination
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 10;
        $total = $this->kategoriModel->countAll();
        $totalPages = max(1, (int) ceil($total / $perPage));

        // Ambil data
        $kategoris = $this->kategoriModel->getPaginated($page, $perPage);

        // Tampilkan halaman
        $this->view('admin/kategori/index', [
            'title' => 'Data Kategori',
            'activeMenu' => 'kategori',
            'user' => Session::user(),
            'kategoris' => $kategoris,
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
        $this->view('admin/kategori/form', [
            'title' => 'Tambah Kategori',
            'activeMenu' => 'kategori',
            'user' => Session::user(),
            'formAction' => '/admin/kategori/store',
            'formMode' => 'create',
            'kategori' => null,
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
            'nama' => trim($_POST['nama'] ?? ''),
            'deskripsi' => trim($_POST['deskripsi'] ?? ''),
        ];

        Session::set('_old', $data);

        $errors = Validator::validate($data, [
            'nama' => ['required', 'max:100'],
        ]);

        if ($this->kategoriModel->namaExists($data['nama'])) {
            $errors['nama'] = 'Nama kategori sudah dipakai.';
        }

        if (!empty($errors)) {
            Session::set('_errors', $errors);
            $this->redirect('/admin/kategori/create');
        }

        // Simpan data
        $created = $this->kategoriModel->create($data);

        if (!$created) {
            Session::setFlash('error', 'Kategori gagal ditambahkan.');
            $this->redirect('/admin/kategori/create');
        }

        Session::remove('_old');
        Session::setFlash('success', 'Kategori berhasil ditambahkan.');
        $this->redirect('/admin/kategori');
    }

    public function edit($id): void
    {
        // Cek akses
        $this->requireRole('admin');

        $id = (int) $id;
        $kategori = $this->kategoriModel->findById($id);

        if (!$kategori) {
            Session::setFlash('error', 'Kategori tidak ditemukan.');
            $this->redirect('/admin/kategori');
        }

        // Tampilkan form
        $this->view('admin/kategori/form', [
            'title' => 'Edit Kategori',
            'activeMenu' => 'kategori',
            'user' => Session::user(),
            'formAction' => '/admin/kategori/update/' . $id,
            'formMode' => 'edit',
            'kategori' => $kategori,
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
        $kategori = $this->kategoriModel->findById($id);

        if (!$kategori) {
            Session::setFlash('error', 'Kategori tidak ditemukan.');
            $this->redirect('/admin/kategori');
        }

        // Validasi input
        $data = [
            'nama' => trim($_POST['nama'] ?? ''),
            'deskripsi' => trim($_POST['deskripsi'] ?? ''),
        ];

        Session::set('_old', $data);

        $errors = Validator::validate($data, [
            'nama' => ['required', 'max:100'],
        ]);

        if ($this->kategoriModel->namaExists($data['nama'], $id)) {
            $errors['nama'] = 'Nama kategori sudah dipakai.';
        }

        if (!empty($errors)) {
            Session::set('_errors', $errors);
            $this->redirect('/admin/kategori/edit/' . $id);
        }

        // Simpan perubahan
        $updated = $this->kategoriModel->update($id, $data);

        if (!$updated) {
            Session::setFlash('error', 'Kategori gagal diperbarui.');
            $this->redirect('/admin/kategori/edit/' . $id);
        }

        Session::remove('_old');
        Session::setFlash('success', 'Kategori berhasil diperbarui.');
        $this->redirect('/admin/kategori');
    }

    public function delete($id): void
    {
        // Cek akses
        $this->requireRole('admin');

        $id = (int) $id;
        $kategori = $this->kategoriModel->findById($id);

        if (!$kategori) {
            Session::setFlash('error', 'Kategori tidak ditemukan.');
            $this->redirect('/admin/kategori');
        }

        if ($this->kategoriModel->isUsedByBarang($id)) {
            Session::setFlash('error', 'Kategori masih dipakai barang, tidak bisa dihapus. Nonaktifkan barang terkait terlebih dahulu.');
            $this->redirect('/admin/kategori');
        }

        // Hapus permanen
        $deleted = $this->kategoriModel->deleteOrDeactivate($id);

        if (!$deleted) {
            Session::setFlash('error', 'Kategori gagal dihapus.');
            $this->redirect('/admin/kategori');
        }

        Session::setFlash('success', 'Kategori berhasil dihapus.');
        $this->redirect('/admin/kategori');
    }
}