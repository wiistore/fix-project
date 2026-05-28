<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailTransaksi;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $tanggalMulai = trim((string) $request->query('tanggal_mulai', ''));
        $tanggalSelesai = trim((string) $request->query('tanggal_selesai', ''));

        $query = DB::table('transaksi as t')
            ->join('users as u', 'u.id', '=', 't.id_user')
            ->select('t.*', 'u.username as nama_kasir')
            ->orderByDesc('t.tanggal')
            ->orderByDesc('t.id');

        $isFiltered = $tanggalMulai !== '' || $tanggalSelesai !== '';

        if ($tanggalMulai !== '') {
            $query->whereDate('t.tanggal', '>=', $tanggalMulai);
        }
        if ($tanggalSelesai !== '') {
            $query->whereDate('t.tanggal', '<=', $tanggalSelesai);
        }

        if ($isFiltered) {
            $transaksis = $query->limit(500)->get()->map(fn ($r) => (array) $r)->all();
            $pagination = null;
        } else {
            $page = max(1, (int) $request->query('page', 1));
            $perPage = 10;
            $total = Transaksi::count();
            $totalPages = max(1, (int) ceil($total / $perPage));

            $transaksis = $query
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

        // Summary dari data yang ditampilkan (exclude dibatalkan)
        $totalTransaksi = 0;
        $totalPenjualan = 0.0;
        $totalModal = 0.0;
        $totalLaba = 0.0;

        foreach ($transaksis as $t) {
            if (($t['status'] ?? 'selesai') === 'dibatalkan') {
                continue;
            }
            $totalTransaksi++;
            $totalPenjualan += (float) ($t['total_jual'] ?? 0);
            $totalModal += (float) ($t['total_beli'] ?? 0);
            $totalLaba += (float) ($t['total_laba'] ?? 0);
        }

        return view('admin.riwayat.index', [
            'title' => 'Riwayat Transaksi',
            'activeMenu' => 'riwayat',
            'user' => current_user_array(),
            'transaksis' => $transaksis,
            'summary' => [
                'total_transaksi' => $totalTransaksi,
                'total_penjualan' => $totalPenjualan,
                'total_modal' => $totalModal,
                'total_laba' => $totalLaba,
            ],
            'pagination' => $pagination,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }

    public function detail($id)
    {
        $transaksi = $this->findTransaksiArray((int) $id);

        if (! $transaksi) {
            return redirect('/admin/riwayat-transaksi')->with('error', 'Transaksi tidak ditemukan.');
        }

        $items = $this->itemsWithBarang((int) $id);

        $detailSummary = [
            'total_item' => count($items),
            'total_qty' => array_sum(array_column($items, 'qty')),
            'total_jual' => array_sum(array_map(fn ($r) => (float) $r['subtotal_jual'], $items)),
            'total_beli' => array_sum(array_map(fn ($r) => (float) $r['subtotal_beli'], $items)),
            'total_laba' => array_sum(array_map(fn ($r) => (float) $r['laba_item'], $items)),
        ];

        return view('admin.riwayat.detail', [
            'title' => 'Detail Transaksi',
            'activeMenu' => 'riwayat',
            'user' => current_user_array(),
            'transaksi' => $transaksi,
            'items' => $items,
            'detailTransaksi' => $items,
            'detailSummary' => $detailSummary,
        ]);
    }

    public function edit(Request $request, $id)
    {
        $transaksi = $this->findTransaksiArray((int) $id);

        if (! $transaksi) {
            return redirect('/admin/riwayat-transaksi')->with('error', 'Transaksi tidak ditemukan.');
        }

        if (($transaksi['status'] ?? 'selesai') === 'dibatalkan') {
            return redirect('/admin/riwayat-transaksi')->with('error', 'Transaksi yang sudah dibatalkan tidak bisa diedit.');
        }

        $items = $this->itemsWithBarang((int) $id);

        $barangs = DB::table('barang as b')
            ->join('kategori as k', 'k.id', '=', 'b.id_kategori')
            ->select('b.*', 'k.nama as nama_kategori')
            ->where('b.status', 'aktif')
            ->orderBy('b.nama')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        return view('admin.riwayat.edit', [
            'title' => 'Edit Transaksi',
            'activeMenu' => 'riwayat',
            'user' => current_user_array(),
            'transaksi' => $transaksi,
            'items' => $items,
            'barangs' => $barangs,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $id = (int) $id;
        $transaksi = $this->findTransaksiArray($id);

        if (! $transaksi) {
            return redirect('/admin/riwayat-transaksi')->with('error', 'Transaksi tidak ditemukan.');
        }

        if (($transaksi['status'] ?? 'selesai') === 'dibatalkan') {
            return redirect('/admin/riwayat-transaksi')->with('error', 'Transaksi yang sudah dibatalkan tidak bisa diedit.');
        }

        $cartJson = trim((string) $request->input('cart_json', ''));
        $nominalBayar = trim((string) $request->input('nominal_bayar', '0'));
        $metodeBayarPost = trim((string) $request->input('metode_bayar', ''));

        $newItems = $this->parseCart($cartJson);

        if (empty($newItems)) {
            return redirect('/admin/riwayat-transaksi/edit/'.$id)
                ->with('error', 'Keranjang tidak boleh kosong. Minimal 1 barang.');
        }

        try {
            DB::transaction(function () use ($id, $transaksi, $newItems, $nominalBayar, $metodeBayarPost) {
                // STEP 1: kembalikan stok lama
                $oldItems = DetailTransaksi::where('id_transaksi', $id)->get();

                foreach ($oldItems as $old) {
                    DB::table('barang')->where('id', $old->id_barang)->increment('stok', $old->qty);
                }

                // STEP 2: hitung ulang dari DB
                $prepared = $this->prepareItems($newItems);

                if (empty($prepared['items'])) {
                    throw new \RuntimeException('Tidak ada barang valid di keranjang baru.');
                }

                // STEP 3: kurangi stok baru
                foreach ($prepared['items'] as $item) {
                    $affected = DB::table('barang')
                        ->where('id', $item['id_barang'])
                        ->where('stok', '>=', $item['qty'])
                        ->decrement('stok', $item['qty']);

                    if ($affected === 0) {
                        throw new \RuntimeException('Stok tidak cukup untuk barang: '.$item['nama']);
                    }
                }

                // STEP 4: hapus detail lama
                DetailTransaksi::where('id_transaksi', $id)->delete();

                // STEP 5: simpan detail baru
                foreach ($prepared['items'] as $item) {
                    DetailTransaksi::create([
                        'id_transaksi' => $id,
                        'id_barang' => $item['id_barang'],
                        'qty' => $item['qty'],
                        'harga_jual' => $item['harga_jual'],
                        'harga_beli' => $item['harga_beli'],
                        'subtotal_jual' => $item['subtotal_jual'],
                        'subtotal_beli' => $item['subtotal_beli'],
                        'laba_item' => $item['laba_item'],
                    ]);
                }

                // STEP 6: update total
                $totalJual = $prepared['total_jual'];

                $metodeBayar = $transaksi['metode_bayar'] ?? 'cash';
                if ($metodeBayarPost !== '' && Transaksi::isValidPaymentMethod($metodeBayarPost)) {
                    $metodeBayar = $metodeBayarPost;
                }

                $nominalBayarValue = $metodeBayar === 'cash' ? (float) $nominalBayar : $totalJual;
                $kembalian = $metodeBayar === 'cash' ? max(0, $nominalBayarValue - $totalJual) : 0;

                if ($metodeBayar === 'cash' && $nominalBayarValue < $totalJual) {
                    throw new \RuntimeException('Nominal bayar kurang dari total transaksi baru.');
                }

                Transaksi::where('id', $id)->update([
                    'total_jual' => $totalJual,
                    'total_beli' => $prepared['total_beli'],
                    'total_laba' => $prepared['total_laba'],
                    'metode_bayar' => $metodeBayar,
                    'nominal_bayar' => $nominalBayarValue,
                    'kembalian' => $kembalian,
                    'status' => 'diubah',
                    'edited_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            return redirect('/admin/riwayat-transaksi/edit/'.$id)
                ->with('error', config('app.debug') ? $e->getMessage() : 'Gagal mengubah transaksi.');
        }

        return redirect('/admin/riwayat-transaksi')->with('success', 'Transaksi berhasil diubah. Stok dan total sudah diperbarui.');
    }

    public function cancel(Request $request, $id)
    {
        $id = (int) $id;
        $alasan = trim((string) $request->input('alasan_batal', ''));

        if ($alasan === '') {
            return redirect('/admin/riwayat-transaksi')->with('error', 'Alasan pembatalan wajib diisi.');
        }

        $transaksi = Transaksi::find($id);

        if (! $transaksi) {
            return redirect('/admin/riwayat-transaksi')->with('error', 'Transaksi tidak ditemukan.');
        }

        if ($transaksi->status === 'dibatalkan') {
            return redirect('/admin/riwayat-transaksi')->with('error', 'Transaksi ini sudah dibatalkan sebelumnya.');
        }

        $items = DetailTransaksi::where('id_transaksi', $id)->get();

        if ($items->isEmpty()) {
            return redirect('/admin/riwayat-transaksi')->with('error', 'Detail transaksi kosong.');
        }

        try {
            DB::transaction(function () use ($id, $items, $alasan) {
                foreach ($items as $item) {
                    DB::table('barang')->where('id', $item->id_barang)->increment('stok', $item->qty);
                }

                Transaksi::where('id', $id)->update([
                    'status' => 'dibatalkan',
                    'alasan_batal' => $alasan,
                ]);
            });
        } catch (\Throwable $e) {
            return redirect('/admin/riwayat-transaksi')
                ->with('error', config('app.debug') ? $e->getMessage() : 'Gagal membatalkan transaksi.');
        }

        return redirect('/admin/riwayat-transaksi')->with('success', 'Transaksi berhasil dibatalkan. Stok barang sudah dikembalikan.');
    }

    private function findTransaksiArray(int $id): ?array
    {
        $row = DB::table('transaksi as t')
            ->join('users as u', 'u.id', '=', 't.id_user')
            ->select('t.*', 'u.username as nama_kasir')
            ->where('t.id', $id)
            ->first();

        return $row ? (array) $row : null;
    }

    private function itemsWithBarang(int $id): array
    {
        return DB::table('detail_transaksi as dt')
            ->join('barang as b', 'b.id', '=', 'dt.id_barang')
            ->select('dt.*', 'b.kode_barang', 'b.barcode', 'b.nama as nama_barang', 'b.satuan')
            ->where('dt.id_transaksi', $id)
            ->orderBy('dt.id')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function parseCart(string $cartJson): array
    {
        if ($cartJson === '') {
            return [];
        }

        $decoded = json_decode($cartJson, true);
        if (! is_array($decoded)) {
            return [];
        }

        $rawItems = $decoded['items'] ?? $decoded;
        $items = [];

        if (! is_array($rawItems)) {
            return [];
        }

        foreach ($rawItems as $item) {
            if (! is_array($item)) {
                continue;
            }
            $idBarang = (int) ($item['id_barang'] ?? $item['id'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);

            if ($idBarang <= 0 || $qty <= 0) {
                continue;
            }

            if (! isset($items[$idBarang])) {
                $items[$idBarang] = ['id_barang' => $idBarang, 'qty' => 0];
            }
            $items[$idBarang]['qty'] += $qty;
        }

        return array_values($items);
    }

    private function prepareItems(array $items): array
    {
        $prepared = [];
        $totalJual = 0.0;
        $totalBeli = 0.0;
        $totalLaba = 0.0;

        foreach ($items as $item) {
            $idBarang = (int) $item['id_barang'];
            $qty = (int) $item['qty'];

            $barang = Barang::where('id', $idBarang)->where('status', 'aktif')->first();

            if (! $barang) {
                throw new \RuntimeException('Barang tidak ditemukan atau nonaktif (ID: '.$idBarang.')');
            }

            if ((int) $barang->stok < $qty) {
                throw new \RuntimeException(
                    'Stok tidak cukup untuk barang: '.$barang->nama.
                    ' (tersedia: '.$barang->stok.', diminta: '.$qty.')'
                );
            }

            $hargaJual = (float) $barang->harga_jual;
            $hargaBeli = $this->getLastHargaBeli($idBarang);

            $subtotalJual = $hargaJual * $qty;
            $subtotalBeli = $hargaBeli * $qty;
            $labaItem = $subtotalJual - $subtotalBeli;

            $prepared[] = [
                'id_barang' => $idBarang,
                'nama' => $barang->nama,
                'qty' => $qty,
                'harga_jual' => $hargaJual,
                'harga_beli' => $hargaBeli,
                'subtotal_jual' => $subtotalJual,
                'subtotal_beli' => $subtotalBeli,
                'laba_item' => $labaItem,
            ];

            $totalJual += $subtotalJual;
            $totalBeli += $subtotalBeli;
            $totalLaba += $labaItem;
        }

        return [
            'items' => $prepared,
            'total_jual' => $totalJual,
            'total_beli' => $totalBeli,
            'total_laba' => $totalLaba,
        ];
    }

    private function getLastHargaBeli(int $idBarang): float
    {
        $row = DB::table('restock')
            ->where('id_barang', $idBarang)
            ->where('tipe', 'masuk')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->first();

        return $row ? (float) $row->harga_beli : 0.0;
    }
}
