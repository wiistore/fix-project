<?php

declare(strict_types=1);

class BarangController extends Controller
{
    private $barangModel;
    private $kategoriModel;

    public function __construct()
    {
        $this->barangModel = $this->model('Barang');
        $this->kategoriModel = $this->model('Kategori');
    }

    public function index(): void
    {
        // Cek akses
        $this->requireRole('admin');

        // Pagination
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 10;
        $total = $this->barangModel->countAll();
        $totalPages = max(1, (int) ceil($total / $perPage));

        // Ambil data
        $barangs = $this->barangModel->getPaginated($page, $perPage);
        $summary = $this->barangModel->summary();

        // Tampilkan halaman
        $this->view('admin/barang/index', [
            'title' => 'Data Barang',
            'activeMenu' => 'barang',
            'user' => Session::user(),
            'barangs' => $barangs,
            'barang' => $barangs,
            'summary' => $summary,
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

        // Ambil kategori
        $kategoris = $this->kategoriModel->getAll();

        if (empty($kategoris)) {
            Session::setFlash('error', 'Buat kategori dulu sebelum tambah barang.');
            $this->redirect('/admin/kategori/create');
        }

        // Tampilkan form
        $this->view('admin/barang/form', [
            'title' => 'Tambah Barang',
            'activeMenu' => 'barang',
            'user' => Session::user(),
            'formAction' => '/admin/barang/store',
            'formMode' => 'create',
            'barang' => null,
            'barangs' => [],
            'kategoris' => $kategoris,
            'kategori' => $kategoris,
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
        $data = $this->payload();

        if ($data['barcode'] === '') {
            $data['barcode'] = $this->generateBarcode();
        }

        Session::set('_old', $data);

        $errors = $this->validatePayload($data);

        if ($this->barangModel->kodeExists($data['kode_barang'])) {
            $errors['kode_barang'] = 'Kode barang sudah dipakai.';
        }

        if ($this->barangModel->barcodeExists($data['barcode'])) {
            $errors['barcode'] = 'Barcode sudah dipakai.';
        }

        if (!empty($errors)) {
            Session::set('_errors', $errors);
            $this->redirect('/admin/barang/create');
        }

        // Simpan data
        $created = $this->barangModel->create($data);

        if (!$created) {
            Session::setFlash('error', 'Barang gagal ditambahkan.');
            $this->redirect('/admin/barang/create');
        }

        Session::remove('_old');
        Session::setFlash('success', 'Barang berhasil ditambahkan.');
        $this->redirect('/admin/barang');
    }

    public function edit($id): void
    {
        // Cek akses
        $this->requireRole('admin');

        $id = (int) $id;
        $barang = $this->barangModel->findById($id);

        if (!$barang) {
            Session::setFlash('error', 'Barang tidak ditemukan.');
            $this->redirect('/admin/barang');
        }

        // Ambil kategori
        $kategoris = $this->kategoriModel->getAll();

        if (empty($kategoris)) {
            Session::setFlash('error', 'Kategori belum tersedia.');
            $this->redirect('/admin/barang');
        }

        // Tampilkan form
        $this->view('admin/barang/form', [
            'title' => 'Edit Barang',
            'activeMenu' => 'barang',
            'user' => Session::user(),
            'formAction' => '/admin/barang/update/' . $id,
            'formMode' => 'edit',
            'barang' => $barang,
            'barangs' => [],
            'kategoris' => $kategoris,
            'kategori' => $kategoris,
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
        $barang = $this->barangModel->findById($id);

        if (!$barang) {
            Session::setFlash('error', 'Barang tidak ditemukan.');
            $this->redirect('/admin/barang');
        }

        // Validasi input
        $data = $this->payload();

        if ($data['barcode'] === '') {
            $data['barcode'] = $barang['barcode'] ?: $this->generateBarcode();
        }

        Session::set('_old', $data);

        $errors = $this->validatePayload($data);

        if ($this->barangModel->kodeExists($data['kode_barang'], $id)) {
            $errors['kode_barang'] = 'Kode barang sudah dipakai.';
        }

        if ($this->barangModel->barcodeExists($data['barcode'], $id)) {
            $errors['barcode'] = 'Barcode sudah dipakai.';
        }

        if (!empty($errors)) {
            Session::set('_errors', $errors);
            $this->redirect('/admin/barang/edit/' . $id);
        }

        // Simpan perubahan
        $updated = $this->barangModel->update($id, $data);

        if (!$updated) {
            Session::setFlash('error', 'Barang gagal diperbarui.');
            $this->redirect('/admin/barang/edit/' . $id);
        }

        Session::remove('_old');
        Session::setFlash('success', 'Barang berhasil diperbarui.');
        $this->redirect('/admin/barang');
    }

    public function delete($id): void
    {
        // Cek akses
        $this->requireRole('admin');

        $id = (int) $id;
        $barang = $this->barangModel->findById($id);

        if (!$barang) {
            Session::setFlash('error', 'Barang tidak ditemukan.');
            $this->redirect('/admin/barang');
        }

        // Cek relasi - jika punya history, tolak hapus permanen
        $hasHistory = $this->barangModel->hasHistory($id);

        if ($hasHistory) {
            Session::setFlash('error', 'Barang memiliki histori transaksi/restock. Gunakan toggle aktif/nonaktif.');
            $this->redirect('/admin/barang');
        }

        // Hapus permanen (tidak punya relasi)
        $deleted = $this->barangModel->deleteOrDeactivate($id);

        if (!$deleted) {
            Session::setFlash('error', 'Barang gagal dihapus.');
            $this->redirect('/admin/barang');
        }

        Session::setFlash('success', 'Barang berhasil dihapus permanen.');
        $this->redirect('/admin/barang');
    }

    public function toggleStatus($id): void
    {
        $this->requireRole('admin');

        $id = (int) $id;
        $barang = $this->barangModel->findById($id);

        if (!$barang) {
            Session::setFlash('error', 'Barang tidak ditemukan.');
            $this->redirect('/admin/barang');
        }

        $currentStatus = strtolower((string) ($barang['status'] ?? 'aktif'));
        $newStatus = $currentStatus === 'aktif' ? 'nonaktif' : 'aktif';

        $updated = $this->barangModel->updateStatus($id, $newStatus);

        if (!$updated) {
            Session::setFlash('error', 'Gagal mengubah status barang.');
            $this->redirect('/admin/barang');
        }

        $label = $newStatus === 'aktif' ? 'diaktifkan' : 'dinonaktifkan';
        Session::setFlash('success', 'Barang berhasil ' . $label . '.');
        $this->redirect('/admin/barang');
    }

    private function payload(): array
    {
        return [
            'kode_barang' => trim($_POST['kode_barang'] ?? ''),
            'barcode' => trim($_POST['barcode'] ?? ''),
            'nama' => trim($_POST['nama'] ?? ''),
            'id_kategori' => trim($_POST['id_kategori'] ?? ''),
            'satuan' => trim($_POST['satuan'] ?? 'pcs'),
            'harga_jual' => trim($_POST['harga_jual'] ?? ''),
            'stok_minimum' => trim($_POST['stok_minimum'] ?? '5'),
            'status' => trim($_POST['status'] ?? 'aktif'),
        ];
    }

    private function validatePayload(array $data): array
    {
        // Validasi dasar
        $errors = Validator::validate($data, [
            'kode_barang' => ['required', 'max:50'],
            'barcode' => ['required', 'max:100'],
            'nama' => ['required', 'max:150'],
            'id_kategori' => ['required', 'integer'],
            'satuan' => ['required', 'max:30'],
            'harga_jual' => ['required', 'numeric'],
            'stok_minimum' => ['required', 'integer'],
        ]);

        // Validasi tambahan
        if ($data['harga_jual'] !== '' && (float) $data['harga_jual'] <= 0) {
            $errors['harga_jual'] = 'Harga jual harus lebih dari 0.';
        }

        if ($data['stok_minimum'] !== '' && (int) $data['stok_minimum'] < 0) {
            $errors['stok_minimum'] = 'Stok minimum tidak boleh minus.';
        }

        if (!Validator::in($data['status'], ['aktif', 'nonaktif'])) {
            $errors['status'] = 'Status tidak valid.';
        }

        if ($data['id_kategori'] !== '' && !$this->kategoriModel->findById((int) $data['id_kategori'])) {
            $errors['id_kategori'] = 'Kategori tidak ditemukan.';
        }

        return $errors;
    }

    private function generateBarcode(): string
    {
        // Generate barcode internal sequential (KPS0000001, KPS0000002, dst)
        return $this->barangModel->generateNextBarcode('KPS', 7);
    }

    /**
     * Endpoint AJAX: GET /admin/barang/generate-barcode
     * Return JSON {barcode: "KPS0000123"} buat tombol "Generate" di form.
     */
    public function generateBarcodeAjax(): void
    {
        $this->requireRole('admin');

        $barcode = $this->generateBarcode();

        if (class_exists('Response') && method_exists('Response', 'json')) {
            Response::json([
                'success' => true,
                'barcode' => $barcode,
            ]);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'barcode' => $barcode,
        ]);
        exit;
    }

    /**
     * Halaman cetak label barcode untuk 1 barang (dengan opsi qty).
     * URL: /admin/barang/label/{id}?qty=24
     */
    public function label($id): void
    {
        $this->requireRole('admin');

        $id = (int) $id;
        $barang = $this->barangModel->findById($id);

        if (!$barang) {
            Session::setFlash('error', 'Barang tidak ditemukan.');
            $this->redirect('/admin/barang');
        }

        if (empty($barang['barcode'])) {
            Session::setFlash('error', 'Barang ini belum punya barcode. Edit barang dulu untuk generate barcode.');
            $this->redirect('/admin/barang');
        }

        $qty = isset($_GET['qty']) ? (int) $_GET['qty'] : 24;
        $qty = max(1, min(96, $qty));

        // Bikin array item yang akan dicetak (qty x 1 barang)
        $items = [];
        for ($i = 0; $i < $qty; $i++) {
            $items[] = $barang;
        }

        $this->view('admin/barang/label', [
            'title' => 'Cetak Label Barcode',
            'activeMenu' => 'barang',
            'user' => Session::user(),
            'items' => $items,
            'mode' => 'single',
            'sourceBarang' => $barang,
            'qty' => $qty,
        ]);
    }

    /**
     * Halaman cetak label bulk - pilih banyak barang sekaligus.
     * URL: GET /admin/barang/label-bulk?ids=1,2,3
     *      atau POST dengan field ids[]
     */
    public function labelBulk(): void
    {
        $this->requireRole('admin');

        // Ambil ids dari GET atau POST
        $rawIds = [];

        if (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $rawIds = $_POST['ids'];
        } elseif (isset($_GET['ids'])) {
            $rawIds = is_array($_GET['ids'])
                ? $_GET['ids']
                : explode(',', (string) $_GET['ids']);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $rawIds), static fn ($id) => $id > 0)));

        if (empty($ids)) {
            Session::setFlash('error', 'Pilih minimal 1 barang dulu untuk cetak label.');
            $this->redirect('/admin/barang');
        }

        $barangs = $this->barangModel->findManyByIds($ids);

        // Filter: hanya yang punya barcode
        $barangs = array_values(array_filter($barangs, static fn ($b) => !empty($b['barcode'])));

        if (empty($barangs)) {
            Session::setFlash('error', 'Tidak ada barang dengan barcode valid yang dipilih.');
            $this->redirect('/admin/barang');
        }

        $this->view('admin/barang/label', [
            'title' => 'Cetak Label Barcode (Bulk)',
            'activeMenu' => 'barang',
            'user' => Session::user(),
            'items' => $barangs,
            'mode' => 'bulk',
            'sourceBarang' => null,
            'qty' => count($barangs),
        ]);
    }
}