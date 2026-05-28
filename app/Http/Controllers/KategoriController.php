<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KategoriController extends Controller
{
    public function index(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;
        $total = Kategori::count();
        $totalPages = max(1, (int) ceil($total / $perPage));

        $kategoris = Kategori::orderBy('nama')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn ($k) => $k->toArray())
            ->all();

        return view('admin.kategori.index', [
            'title' => 'Data Kategori',
            'activeMenu' => 'kategori',
            'user' => current_user_array(),
            'kategoris' => $kategoris,
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
        return view('admin.kategori.form', [
            'title' => 'Tambah Kategori',
            'activeMenu' => 'kategori',
            'user' => current_user_array(),
            'formAction' => '/admin/kategori/store',
            'formMode' => 'create',
            'kategori' => null,
            'errors' => $request->session()->get('errors_kv', []),
            'old' => $request->session()->getOldInput() ?: [],
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'nama' => trim((string) $request->input('nama', '')),
            'deskripsi' => trim((string) $request->input('deskripsi', '')),
        ];

        $errors = [];

        if ($data['nama'] === '') {
            $errors['nama'] = 'Nama wajib diisi.';
        } elseif (mb_strlen($data['nama']) > 100) {
            $errors['nama'] = 'Nama maksimal 100 karakter.';
        } elseif (Kategori::where('nama', $data['nama'])->exists()) {
            $errors['nama'] = 'Nama kategori sudah dipakai.';
        }

        if (! empty($errors)) {
            return redirect('/admin/kategori/create')
                ->withInput($data)
                ->with('errors_kv', $errors);
        }

        Kategori::create($data);

        return redirect('/admin/kategori')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Request $request, $id)
    {
        $kategori = Kategori::find((int) $id);

        if (! $kategori) {
            return redirect('/admin/kategori')->with('error', 'Kategori tidak ditemukan.');
        }

        return view('admin.kategori.form', [
            'title' => 'Edit Kategori',
            'activeMenu' => 'kategori',
            'user' => current_user_array(),
            'formAction' => '/admin/kategori/update/'.$id,
            'formMode' => 'edit',
            'kategori' => $kategori->toArray(),
            'errors' => $request->session()->get('errors_kv', []),
            'old' => $request->session()->getOldInput() ?: [],
        ]);
    }

    public function update(Request $request, $id)
    {
        $id = (int) $id;
        $kategori = Kategori::find($id);

        if (! $kategori) {
            return redirect('/admin/kategori')->with('error', 'Kategori tidak ditemukan.');
        }

        $data = [
            'nama' => trim((string) $request->input('nama', '')),
            'deskripsi' => trim((string) $request->input('deskripsi', '')),
        ];

        $errors = [];

        if ($data['nama'] === '') {
            $errors['nama'] = 'Nama wajib diisi.';
        } elseif (mb_strlen($data['nama']) > 100) {
            $errors['nama'] = 'Nama maksimal 100 karakter.';
        } elseif (Kategori::where('nama', $data['nama'])->where('id', '!=', $id)->exists()) {
            $errors['nama'] = 'Nama kategori sudah dipakai.';
        }

        if (! empty($errors)) {
            return redirect('/admin/kategori/edit/'.$id)
                ->withInput($data)
                ->with('errors_kv', $errors);
        }

        $kategori->update($data);

        return redirect('/admin/kategori')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function delete($id)
    {
        $id = (int) $id;
        $kategori = Kategori::find($id);

        if (! $kategori) {
            return redirect('/admin/kategori')->with('error', 'Kategori tidak ditemukan.');
        }

        // Cek apakah masih dipakai barang
        if (DB::table('barang')->where('id_kategori', $id)->exists()) {
            return redirect('/admin/kategori')->with('error', 'Kategori masih dipakai barang. Nonaktifkan barang terkait dulu.');
        }

        $kategori->delete();

        return redirect('/admin/kategori')->with('success', 'Kategori berhasil dihapus.');
    }

    public function toggleStatus($id)
    {
        return redirect('/admin/kategori')->with('error', 'Kategori tidak punya fitur toggle status. Hapus saja kalau tidak terpakai.');
    }
}
