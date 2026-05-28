<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;
        $total = Supplier::count();
        $totalPages = max(1, (int) ceil($total / $perPage));

        // Hitung total restock per supplier (mirror native)
        $rows = DB::table('supplier as s')
            ->leftJoin('restock as r', 'r.id_supplier', '=', 's.id')
            ->select('s.*', DB::raw('COUNT(r.id) as total_restock'))
            ->groupBy('s.id', 's.nama', 's.kontak_person', 's.no_hp', 's.alamat', 's.keterangan', 's.status', 's.created_at', 's.updated_at')
            ->orderBy('s.nama')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        return view('admin.supplier.index', [
            'title' => 'Data Supplier',
            'activeMenu' => 'supplier',
            'user' => current_user_array(),
            'suppliers' => $rows,
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
        return view('admin.supplier.form', [
            'title' => 'Tambah Supplier',
            'activeMenu' => 'supplier',
            'user' => current_user_array(),
            'formAction' => '/admin/supplier/store',
            'formMode' => 'create',
            'supplier' => null,
            'errors' => $request->session()->get('errors_kv', []),
            'old' => $request->session()->getOldInput() ?: [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->payload($request);
        $errors = $this->validatePayload($data);

        if (Supplier::where('nama', $data['nama'])->exists()) {
            $errors['nama'] = 'Nama supplier sudah dipakai.';
        }

        if (! empty($errors)) {
            return redirect('/admin/supplier/create')
                ->withInput($data)
                ->with('errors_kv', $errors);
        }

        Supplier::create($data);

        return redirect('/admin/supplier')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Request $request, $id)
    {
        $supplier = Supplier::find((int) $id);

        if (! $supplier) {
            return redirect('/admin/supplier')->with('error', 'Supplier tidak ditemukan.');
        }

        return view('admin.supplier.form', [
            'title' => 'Edit Supplier',
            'activeMenu' => 'supplier',
            'user' => current_user_array(),
            'formAction' => '/admin/supplier/update/'.$id,
            'formMode' => 'edit',
            'supplier' => $supplier->toArray(),
            'errors' => $request->session()->get('errors_kv', []),
            'old' => $request->session()->getOldInput() ?: [],
        ]);
    }

    public function update(Request $request, $id)
    {
        $id = (int) $id;
        $supplier = Supplier::find($id);

        if (! $supplier) {
            return redirect('/admin/supplier')->with('error', 'Supplier tidak ditemukan.');
        }

        $data = $this->payload($request);
        $errors = $this->validatePayload($data);

        if (Supplier::where('nama', $data['nama'])->where('id', '!=', $id)->exists()) {
            $errors['nama'] = 'Nama supplier sudah dipakai.';
        }

        if (! empty($errors)) {
            return redirect('/admin/supplier/edit/'.$id)
                ->withInput($data)
                ->with('errors_kv', $errors);
        }

        $supplier->update($data);

        return redirect('/admin/supplier')->with('success', 'Supplier berhasil diperbarui.');
    }

    public function delete($id)
    {
        $id = (int) $id;
        $supplier = Supplier::find($id);

        if (! $supplier) {
            return redirect('/admin/supplier')->with('error', 'Supplier tidak ditemukan.');
        }

        $hasRestock = DB::table('restock')->where('id_supplier', $id)->exists();

        if ($hasRestock) {
            $supplier->update(['status' => 'nonaktif']);

            return redirect('/admin/supplier')->with('success', 'Supplier dipakai histori, dinonaktifkan.');
        }

        $supplier->delete();

        return redirect('/admin/supplier')->with('success', 'Supplier berhasil dihapus permanen.');
    }

    public function toggleStatus($id)
    {
        $id = (int) $id;
        $supplier = Supplier::find($id);

        if (! $supplier) {
            return redirect('/admin/supplier')->with('error', 'Supplier tidak ditemukan.');
        }

        $newStatus = $supplier->status === 'aktif' ? 'nonaktif' : 'aktif';
        $supplier->update(['status' => $newStatus]);

        $label = $newStatus === 'aktif' ? 'diaktifkan' : 'dinonaktifkan';

        return redirect('/admin/supplier')->with('success', 'Supplier berhasil '.$label.'.');
    }

    private function payload(Request $request): array
    {
        return [
            'nama' => trim((string) $request->input('nama', '')),
            'kontak_person' => trim((string) $request->input('kontak_person', '')) ?: null,
            'no_hp' => trim((string) $request->input('no_hp', '')) ?: null,
            'alamat' => trim((string) $request->input('alamat', '')) ?: null,
            'keterangan' => trim((string) $request->input('keterangan', '')) ?: null,
            'status' => trim((string) $request->input('status', 'aktif')),
        ];
    }

    private function validatePayload(array $data): array
    {
        $errors = [];

        if ($data['nama'] === null || $data['nama'] === '') {
            $errors['nama'] = 'Nama wajib diisi.';
        } elseif (mb_strlen($data['nama']) > 150) {
            $errors['nama'] = 'Nama maksimal 150 karakter.';
        }

        if ($data['kontak_person'] && mb_strlen($data['kontak_person']) > 100) {
            $errors['kontak_person'] = 'Kontak person maksimal 100 karakter.';
        }

        if ($data['no_hp'] && mb_strlen($data['no_hp']) > 20) {
            $errors['no_hp'] = 'No HP maksimal 20 karakter.';
        }

        if (! in_array($data['status'], ['aktif', 'nonaktif'], true)) {
            $errors['status'] = 'Status tidak valid.';
        }

        return $errors;
    }
}
