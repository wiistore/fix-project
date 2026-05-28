<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangController extends Controller
{
    public function index(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;
        $total = Barang::count();
        $totalPages = max(1, (int) ceil($total / $perPage));

        $barangs = DB::table('barang as b')
            ->join('kategori as k', 'k.id', '=', 'b.id_kategori')
            ->select('b.*', 'k.nama as nama_kategori')
            ->orderBy('b.nama')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        $summaryRow = DB::table('barang')->selectRaw("
            COUNT(id) as total_barang,
            SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as barang_aktif,
            SUM(CASE WHEN status = 'nonaktif' THEN 1 ELSE 0 END) as barang_nonaktif,
            SUM(CASE WHEN status = 'aktif' AND stok <= stok_minimum THEN 1 ELSE 0 END) as stok_menipis
        ")->first();

        $summary = [
            'total_barang' => (int) ($summaryRow->total_barang ?? 0),
            'barang_aktif' => (int) ($summaryRow->barang_aktif ?? 0),
            'barang_nonaktif' => (int) ($summaryRow->barang_nonaktif ?? 0),
            'stok_menipis' => (int) ($summaryRow->stok_menipis ?? 0),
        ];

        return view('admin.barang.index', [
            'title' => 'Data Barang',
            'activeMenu' => 'barang',
            'user' => current_user_array(),
            'barangs' => $barangs,
            'summary' => $summary,
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
        $kategoris = Kategori::orderBy('nama')->get()->map(fn ($k) => $k->toArray())->all();

        if (empty($kategoris)) {
            return redirect('/admin/kategori/create')->with('error', 'Buat kategori dulu sebelum tambah barang.');
        }

        return view('admin.barang.form', [
            'title' => 'Tambah Barang',
            'activeMenu' => 'barang',
            'user' => current_user_array(),
            'formAction' => '/admin/barang/store',
            'formMode' => 'create',
            'barang' => null,
            'kategoris' => $kategoris,
            'errors' => $request->session()->get('errors_kv', []),
            'old' => $request->session()->getOldInput() ?: [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->payload($request);

        if ($data['barcode'] === '') {
            $data['barcode'] = $this->generateBarcode();
        }

        $errors = $this->validatePayload($data);

        if (Barang::where('kode_barang', $data['kode_barang'])->exists()) {
            $errors['kode_barang'] = 'Kode barang sudah dipakai.';
        }

        if (Barang::where('barcode', $data['barcode'])->exists()) {
            $errors['barcode'] = 'Barcode sudah dipakai.';
        }

        if (! empty($errors)) {
            return redirect('/admin/barang/create')
                ->withInput($data)
                ->with('errors_kv', $errors);
        }

        Barang::create($data + ['stok' => 0]);

        return redirect('/admin/barang')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function edit(Request $request, $id)
    {
        $barang = Barang::find((int) $id);

        if (! $barang) {
            return redirect('/admin/barang')->with('error', 'Barang tidak ditemukan.');
        }

        $kategoris = Kategori::orderBy('nama')->get()->map(fn ($k) => $k->toArray())->all();

        return view('admin.barang.form', [
            'title' => 'Edit Barang',
            'activeMenu' => 'barang',
            'user' => current_user_array(),
            'formAction' => '/admin/barang/update/'.$id,
            'formMode' => 'edit',
            'barang' => $barang->toArray(),
            'kategoris' => $kategoris,
            'errors' => $request->session()->get('errors_kv', []),
            'old' => $request->session()->getOldInput() ?: [],
        ]);
    }

    public function update(Request $request, $id)
    {
        $id = (int) $id;
        $barang = Barang::find($id);

        if (! $barang) {
            return redirect('/admin/barang')->with('error', 'Barang tidak ditemukan.');
        }

        $data = $this->payload($request);

        if ($data['barcode'] === '') {
            $data['barcode'] = $barang->barcode ?: $this->generateBarcode();
        }

        $errors = $this->validatePayload($data);

        if (Barang::where('kode_barang', $data['kode_barang'])->where('id', '!=', $id)->exists()) {
            $errors['kode_barang'] = 'Kode barang sudah dipakai.';
        }

        if (Barang::where('barcode', $data['barcode'])->where('id', '!=', $id)->exists()) {
            $errors['barcode'] = 'Barcode sudah dipakai.';
        }

        if (! empty($errors)) {
            return redirect('/admin/barang/edit/'.$id)
                ->withInput($data)
                ->with('errors_kv', $errors);
        }

        $barang->update($data);

        return redirect('/admin/barang')->with('success', 'Barang berhasil diperbarui.');
    }

    public function delete($id)
    {
        $id = (int) $id;
        $barang = Barang::find($id);

        if (! $barang) {
            return redirect('/admin/barang')->with('error', 'Barang tidak ditemukan.');
        }

        $hasHistory = DB::table('restock')->where('id_barang', $id)->exists()
            || DB::table('detail_transaksi')->where('id_barang', $id)->exists();

        if ($hasHistory) {
            return redirect('/admin/barang')->with('error', 'Barang punya histori. Gunakan toggle aktif/nonaktif.');
        }

        $barang->delete();

        return redirect('/admin/barang')->with('success', 'Barang berhasil dihapus permanen.');
    }

    public function toggleStatus($id)
    {
        $id = (int) $id;
        $barang = Barang::find($id);

        if (! $barang) {
            return redirect('/admin/barang')->with('error', 'Barang tidak ditemukan.');
        }

        $newStatus = $barang->status === 'aktif' ? 'nonaktif' : 'aktif';
        $barang->update(['status' => $newStatus]);

        $label = $newStatus === 'aktif' ? 'diaktifkan' : 'dinonaktifkan';

        return redirect('/admin/barang')->with('success', 'Barang berhasil '.$label.'.');
    }

    public function generateBarcodeAjax()
    {
        return response()->json([
            'success' => true,
            'barcode' => $this->generateBarcode(),
        ]);
    }

    public function label(Request $request, $id)
    {
        $id = (int) $id;
        $barang = DB::table('barang as b')
            ->join('kategori as k', 'k.id', '=', 'b.id_kategori')
            ->select('b.*', 'k.nama as nama_kategori')
            ->where('b.id', $id)
            ->first();

        if (! $barang) {
            return redirect('/admin/barang')->with('error', 'Barang tidak ditemukan.');
        }

        if (empty($barang->barcode)) {
            return redirect('/admin/barang')->with('error', 'Barang ini belum punya barcode.');
        }

        $qty = max(1, min(96, (int) $request->query('qty', 24)));
        $items = array_fill(0, $qty, (array) $barang);

        return view('admin.barang.label', [
            'title' => 'Cetak Label Barcode',
            'activeMenu' => 'barang',
            'user' => current_user_array(),
            'items' => $items,
            'mode' => 'single',
            'sourceBarang' => (array) $barang,
            'qty' => $qty,
        ]);
    }

    public function labelBulk(Request $request)
    {
        $rawIds = $request->input('ids', $request->query('ids', []));

        if (is_string($rawIds)) {
            $rawIds = explode(',', $rawIds);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $rawIds), fn ($v) => $v > 0)));

        if (empty($ids)) {
            return redirect('/admin/barang')->with('error', 'Pilih minimal 1 barang dulu untuk cetak label.');
        }

        $items = DB::table('barang')
            ->whereIn('id', $ids)
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->orderBy('nama')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        if (empty($items)) {
            return redirect('/admin/barang')->with('error', 'Tidak ada barang dengan barcode valid yang dipilih.');
        }

        return view('admin.barang.label', [
            'title' => 'Cetak Label Barcode (Bulk)',
            'activeMenu' => 'barang',
            'user' => current_user_array(),
            'items' => $items,
            'mode' => 'bulk',
            'sourceBarang' => null,
            'qty' => count($items),
        ]);
    }

    private function payload(Request $request): array
    {
        return [
            'kode_barang' => trim((string) $request->input('kode_barang', '')),
            'barcode' => trim((string) $request->input('barcode', '')),
            'nama' => trim((string) $request->input('nama', '')),
            'id_kategori' => trim((string) $request->input('id_kategori', '')),
            'satuan' => trim((string) $request->input('satuan', 'pcs')) ?: 'pcs',
            'harga_jual' => trim((string) $request->input('harga_jual', '')),
            'stok_minimum' => trim((string) $request->input('stok_minimum', '5')),
            'status' => trim((string) $request->input('status', 'aktif')),
        ];
    }

    private function validatePayload(array $data): array
    {
        $errors = [];

        if ($data['kode_barang'] === '') {
            $errors['kode_barang'] = 'Kode barang wajib diisi.';
        } elseif (mb_strlen($data['kode_barang']) > 50) {
            $errors['kode_barang'] = 'Kode barang maksimal 50 karakter.';
        }

        if ($data['barcode'] === '') {
            $errors['barcode'] = 'Barcode wajib diisi.';
        } elseif (mb_strlen($data['barcode']) > 100) {
            $errors['barcode'] = 'Barcode maksimal 100 karakter.';
        }

        if ($data['nama'] === '') {
            $errors['nama'] = 'Nama wajib diisi.';
        } elseif (mb_strlen($data['nama']) > 150) {
            $errors['nama'] = 'Nama maksimal 150 karakter.';
        }

        if ($data['id_kategori'] === '' || ! filter_var($data['id_kategori'], FILTER_VALIDATE_INT)) {
            $errors['id_kategori'] = 'Kategori wajib dipilih.';
        } elseif (! Kategori::where('id', (int) $data['id_kategori'])->exists()) {
            $errors['id_kategori'] = 'Kategori tidak ditemukan.';
        }

        if ($data['satuan'] === '') {
            $errors['satuan'] = 'Satuan wajib diisi.';
        }

        if ($data['harga_jual'] === '' || ! is_numeric($data['harga_jual'])) {
            $errors['harga_jual'] = 'Harga jual harus berupa angka.';
        } elseif ((float) $data['harga_jual'] <= 0) {
            $errors['harga_jual'] = 'Harga jual harus lebih dari 0.';
        }

        if ($data['stok_minimum'] === '' || ! filter_var($data['stok_minimum'], FILTER_VALIDATE_INT)) {
            $errors['stok_minimum'] = 'Stok minimum harus berupa angka bulat.';
        } elseif ((int) $data['stok_minimum'] < 0) {
            $errors['stok_minimum'] = 'Stok minimum tidak boleh minus.';
        }

        if (! in_array($data['status'], ['aktif', 'nonaktif'], true)) {
            $errors['status'] = 'Status tidak valid.';
        }

        return $errors;
    }

    private function generateBarcode(): string
    {
        $prefix = 'KPS';
        $padLength = 7;

        $row = DB::table('barang')
            ->where('barcode', 'LIKE', $prefix.'%')
            ->orderByRaw('LENGTH(barcode) DESC')
            ->orderByDesc('barcode')
            ->first();

        $next = 1;

        if ($row && ! empty($row->barcode)) {
            $numeric = (int) preg_replace('/\D/', '', preg_replace('/^'.preg_quote($prefix, '/').'/', '', (string) $row->barcode));
            if ($numeric > 0) {
                $next = $numeric + 1;
            }
        }

        for ($i = 0; $i < 10; $i++) {
            $candidate = $prefix.str_pad((string) $next, $padLength, '0', STR_PAD_LEFT);

            if (! Barang::where('barcode', $candidate)->exists()) {
                return $candidate;
            }

            $next++;
        }

        return $prefix.str_pad((string) (time() % 9999999), $padLength, '0', STR_PAD_LEFT);
    }
}
