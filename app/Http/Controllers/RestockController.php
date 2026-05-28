<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Restock;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestockController extends Controller
{
    public function index(Request $request)
    {
        $tanggalMulai = trim((string) $request->query('tanggal_mulai', ''));
        $tanggalSelesai = trim((string) $request->query('tanggal_selesai', ''));
        $filterTipe = trim((string) $request->query('tipe', ''));

        $query = $this->baseQuery();
        $isFiltered = $tanggalMulai !== '' || $tanggalSelesai !== '' || $filterTipe !== '';

        if ($tanggalMulai !== '') {
            $query->where('r.tanggal', '>=', $tanggalMulai);
        }
        if ($tanggalSelesai !== '') {
            $query->where('r.tanggal', '<=', $tanggalSelesai);
        }
        if (in_array($filterTipe, ['masuk', 'keluar'], true)) {
            $query->where('r.tipe', $filterTipe);
        }

        if ($isFiltered) {
            $restocks = $query->limit(500)->get()->map(fn ($r) => (array) $r)->all();
            $pagination = null;
        } else {
            $page = max(1, (int) $request->query('page', 1));
            $perPage = 10;
            $total = Restock::count();
            $totalPages = max(1, (int) ceil($total / $perPage));

            $restocks = $query
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();

            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ];
        }

        $summary = $this->summary($tanggalMulai, $tanggalSelesai, $filterTipe);

        return view('admin.restock.index', [
            'title' => 'Restock & Penyesuaian Stok',
            'activeMenu' => 'restock',
            'user' => current_user_array(),
            'restocks' => $restocks,
            'summary' => $summary,
            'pagination' => $pagination,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'filterTipe' => $filterTipe,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $tipe = trim((string) $request->query('tipe', 'masuk'));
        if (! in_array($tipe, ['masuk', 'keluar'], true)) {
            $tipe = 'masuk';
        }

        $barangs = DB::table('barang as b')
            ->join('kategori as k', 'k.id', '=', 'b.id_kategori')
            ->select('b.*', 'k.nama as nama_kategori')
            ->where('b.status', 'aktif')
            ->orderBy('b.nama')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        $suppliers = Supplier::where('status', 'aktif')->orderBy('nama')->get()
            ->map(fn ($s) => $s->toArray())
            ->all();

        if (empty($barangs)) {
            return redirect('/admin/barang/create')->with('error', 'Belum ada barang aktif. Tambahkan barang dulu.');
        }

        if ($tipe === 'masuk' && empty($suppliers)) {
            return redirect('/admin/supplier/create')->with('error', 'Belum ada supplier aktif. Tambahkan supplier dulu.');
        }

        return view('admin.restock.form', [
            'title' => $tipe === 'masuk' ? 'Tambah Stok Masuk' : 'Kurangi Stok',
            'activeMenu' => 'restock',
            'user' => current_user_array(),
            'tipe' => $tipe,
            'barangs' => $barangs,
            'suppliers' => $suppliers,
            'formAction' => '/admin/restock/store',
            'errors' => $request->session()->get('errors_kv', []),
            'old' => $request->session()->getOldInput() ?: [],
        ]);
    }

    public function store(Request $request)
    {
        $tipe = trim((string) $request->input('tipe', 'masuk'));
        if (! in_array($tipe, ['masuk', 'keluar'], true)) {
            $tipe = 'masuk';
        }

        $data = [
            'tipe' => $tipe,
            'tanggal' => trim((string) $request->input('tanggal', date('Y-m-d'))),
            'id_barang' => trim((string) $request->input('id_barang', '')),
            'id_supplier' => trim((string) $request->input('id_supplier', '')),
            'qty' => trim((string) $request->input('qty', '')),
            'harga_beli' => trim((string) $request->input('harga_beli', '')),
            'harga_jual_baru' => trim((string) $request->input('harga_jual_baru', '')),
            'catatan' => trim((string) $request->input('catatan', '')),
            'alasan' => trim((string) $request->input('alasan', '')),
            'id_user' => (int) auth()->id(),
        ];

        $errors = $this->validatePayload($data);

        $barang = null;

        if (empty($errors['id_barang'])) {
            $barang = Barang::where('id', (int) $data['id_barang'])
                ->where('status', 'aktif')
                ->first();

            if (! $barang) {
                $errors['id_barang'] = 'Barang tidak ditemukan atau sedang nonaktif.';
            }
        }

        if ($tipe === 'masuk' && empty($errors['id_supplier'])) {
            $supplier = Supplier::find((int) $data['id_supplier']);
            if (! $supplier) {
                $errors['id_supplier'] = 'Supplier tidak ditemukan.';
            } elseif ($supplier->status !== 'aktif') {
                $errors['id_supplier'] = 'Supplier sedang nonaktif.';
            }
        }

        if ($tipe === 'keluar' && $barang && empty($errors['qty'])) {
            $stokSekarang = (int) $barang->stok;
            $qtyKeluar = (int) $data['qty'];

            if ($qtyKeluar > $stokSekarang) {
                $errors['qty'] = 'Qty keluar ('.$qtyKeluar.') melebihi stok tersedia ('.$stokSekarang.').';
            }
        }

        if (! empty($errors)) {
            return redirect('/admin/restock/create?tipe='.$tipe)
                ->withInput($data)
                ->with('errors_kv', $errors);
        }

        try {
            DB::transaction(function () use ($data, $tipe) {
                $qty = (int) $data['qty'];
                $hargaBeli = (float) $data['harga_beli'];
                $totalNilai = $qty * $hargaBeli;

                Restock::create([
                    'tipe' => $tipe,
                    'tanggal' => $data['tanggal'],
                    'id_barang' => (int) $data['id_barang'],
                    'id_supplier' => $tipe === 'masuk'
                        ? (int) $data['id_supplier']
                        : ($data['id_supplier'] !== '' ? (int) $data['id_supplier'] : null),
                    'id_user' => $data['id_user'],
                    'qty' => $qty,
                    'harga_beli' => $hargaBeli,
                    'harga_jual_baru' => $data['harga_jual_baru'] !== '' ? (float) $data['harga_jual_baru'] : null,
                    'total_nilai' => $totalNilai,
                    'catatan' => $data['catatan'] ?: null,
                    'alasan' => $tipe === 'keluar' ? ($data['alasan'] ?: null) : null,
                    'created_at' => now(),
                ]);

                if ($tipe === 'masuk') {
                    DB::table('barang')->where('id', (int) $data['id_barang'])->increment('stok', $qty);

                    if ($data['harga_jual_baru'] !== '') {
                        DB::table('barang')->where('id', (int) $data['id_barang'])->update([
                            'harga_jual' => (float) $data['harga_jual_baru'],
                        ]);
                    }
                } else {
                    $affected = DB::table('barang')
                        ->where('id', (int) $data['id_barang'])
                        ->where('stok', '>=', $qty)
                        ->decrement('stok', $qty);

                    if ($affected === 0) {
                        throw new \RuntimeException('Stok barang tidak cukup atau gagal dikurangi.');
                    }
                }
            });
        } catch (\Throwable $e) {
            return redirect('/admin/restock/create?tipe='.$tipe)
                ->withInput($data)
                ->with('error', config('app.debug') ? $e->getMessage() : 'Restock gagal disimpan.');
        }

        $msg = $tipe === 'masuk'
            ? 'Restock berhasil disimpan dan stok bertambah.'
            : 'Stok barang berhasil dikurangi.';

        return redirect('/admin/restock')->with('success', $msg);
    }

    private function baseQuery()
    {
        return DB::table('restock as r')
            ->join('barang as b', 'b.id', '=', 'r.id_barang')
            ->leftJoin('supplier as s', 's.id', '=', 'r.id_supplier')
            ->join('users as u', 'u.id', '=', 'r.id_user')
            ->select(
                'r.*',
                'b.kode_barang',
                'b.nama as nama_barang',
                'b.satuan',
                DB::raw("COALESCE(s.nama, '-') as nama_supplier"),
                'u.username as dibuat_oleh'
            )
            ->orderByDesc('r.tanggal')
            ->orderByDesc('r.id');
    }

    private function summary(string $start, string $end, string $tipe): array
    {
        $q = DB::table('restock');

        if ($start !== '') {
            $q->where('tanggal', '>=', $start);
        }
        if ($end !== '') {
            $q->where('tanggal', '<=', $end);
        }
        if (in_array($tipe, ['masuk', 'keluar'], true)) {
            $q->where('tipe', $tipe);
        }

        $row = $q->selectRaw("
            COUNT(id) as total_restock,
            COALESCE(SUM(CASE WHEN tipe = 'masuk' THEN qty ELSE 0 END), 0) as total_qty_masuk,
            COALESCE(SUM(CASE WHEN tipe = 'keluar' THEN qty ELSE 0 END), 0) as total_qty_keluar,
            COALESCE(SUM(qty), 0) as total_qty,
            COALESCE(SUM(total_nilai), 0) as total_nilai
        ")->first();

        return [
            'total_restock' => (int) ($row->total_restock ?? 0),
            'total_qty_masuk' => (int) ($row->total_qty_masuk ?? 0),
            'total_qty_keluar' => (int) ($row->total_qty_keluar ?? 0),
            'total_qty' => (int) ($row->total_qty ?? 0),
            'total_nilai' => (float) ($row->total_nilai ?? 0),
        ];
    }

    private function validatePayload(array $data): array
    {
        $errors = [];
        $tipe = $data['tipe'] ?? 'masuk';

        if ($data['tanggal'] === '' || ! \DateTime::createFromFormat('Y-m-d', $data['tanggal'])) {
            $errors['tanggal'] = 'Tanggal harus format YYYY-MM-DD.';
        }

        if ($data['id_barang'] === '' || ! filter_var($data['id_barang'], FILTER_VALIDATE_INT)) {
            $errors['id_barang'] = 'Barang wajib dipilih.';
        }

        if ($tipe === 'masuk') {
            if ($data['id_supplier'] === '' || ! filter_var($data['id_supplier'], FILTER_VALIDATE_INT)) {
                $errors['id_supplier'] = 'Supplier wajib dipilih.';
            }
        }

        if ($data['qty'] === '' || ! filter_var($data['qty'], FILTER_VALIDATE_INT) || (int) $data['qty'] <= 0) {
            $errors['qty'] = 'Qty harus angka lebih dari 0.';
        }

        if ($data['harga_beli'] === '' || ! is_numeric($data['harga_beli']) || (float) $data['harga_beli'] <= 0) {
            $errors['harga_beli'] = 'Harga beli harus lebih dari 0.';
        }

        if ($data['harga_jual_baru'] !== '') {
            if (! is_numeric($data['harga_jual_baru']) || (float) $data['harga_jual_baru'] <= 0) {
                $errors['harga_jual_baru'] = 'Harga jual baru harus lebih dari 0.';
            }
        }

        if ($tipe === 'keluar' && $data['alasan'] === '') {
            $errors['alasan'] = 'Alasan pengurangan stok wajib diisi.';
        }

        if ((int) ($data['id_user'] ?? 0) <= 0) {
            $errors['id_user'] = 'Session user tidak valid.';
        }

        return $errors;
    }
}
