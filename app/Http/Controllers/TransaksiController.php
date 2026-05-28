<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailTransaksi;
use App\Models\Transaksi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    public function adminIndex(Request $request)
    {
        return view('admin.transaksi.index', [
            'title' => 'Transaksi Admin',
            'activeMenu' => 'transaksi',
            'user' => current_user_array(),
            'barangs' => $this->activeBarangs(),
            'metodePembayaran' => $this->paymentMethods(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }

    public function kasirIndex(Request $request)
    {
        return view('kasir.transaksi.transaksi', [
            'title' => 'Transaksi Kasir',
            'activeMenu' => 'transaksi',
            'user' => current_user_array(),
            'barangs' => $this->activeBarangs(),
            'metodePembayaran' => $this->paymentMethods(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $role = auth()->user()->role;
        $backUrl = $role === 'admin' ? '/admin/transaksi' : '/kasir/transaksi';

        $cartJson = trim((string) $request->input('cart_json', ''));
        $metodeBayar = trim((string) $request->input('metode_pembayaran', $request->input('metode_bayar', '')));
        $nominalBayar = trim((string) $request->input('nominal_bayar', '0'));

        $items = $this->normalizeCart($cartJson);
        $errors = $this->validateInput($items, $metodeBayar, $nominalBayar);

        if (! empty($errors)) {
            return redirect($backUrl)->with('error', implode(' ', $errors));
        }

        try {
            $transaksiId = DB::transaction(function () use ($items, $metodeBayar, $nominalBayar) {
                $prepared = $this->prepareItems($items);

                if (empty($prepared['items'])) {
                    throw new \RuntimeException('Keranjang tidak valid.');
                }

                $totalJual = $prepared['total_jual'];
                $nominalBayarValue = $metodeBayar === 'cash' ? (float) $nominalBayar : $totalJual;
                $kembalian = $metodeBayar === 'cash' ? $nominalBayarValue - $totalJual : 0;

                if ($metodeBayar === 'cash' && $nominalBayarValue < $totalJual) {
                    throw new \RuntimeException('Nominal bayar kurang dari total transaksi.');
                }

                $transaksi = Transaksi::create([
                    'kode_transaksi' => Transaksi::generateKode(),
                    'id_user' => (int) auth()->id(),
                    'tanggal' => now(),
                    'total_jual' => $totalJual,
                    'total_beli' => $prepared['total_beli'],
                    'total_laba' => $prepared['total_laba'],
                    'metode_bayar' => $metodeBayar,
                    'nominal_bayar' => $nominalBayarValue,
                    'kembalian' => $kembalian,
                    'status' => 'selesai',
                    'created_at' => now(),
                ]);

                foreach ($prepared['items'] as $item) {
                    DetailTransaksi::create([
                        'id_transaksi' => $transaksi->id,
                        'id_barang' => $item['id_barang'],
                        'qty' => $item['qty'],
                        'harga_jual' => $item['harga_jual'],
                        'harga_beli' => $item['harga_beli'],
                        'subtotal_jual' => $item['subtotal_jual'],
                        'subtotal_beli' => $item['subtotal_beli'],
                        'laba_item' => $item['laba_item'],
                    ]);

                    $affected = DB::table('barang')
                        ->where('id', $item['id_barang'])
                        ->where('stok', '>=', $item['qty'])
                        ->decrement('stok', $item['qty']);

                    if ($affected === 0) {
                        throw new \RuntimeException('Stok tidak cukup.');
                    }
                }

                return $transaksi->id;
            });
        } catch (\Throwable $e) {
            return redirect($backUrl)->with('error', config('app.debug') ? $e->getMessage() : 'Transaksi gagal disimpan.');
        }

        $strukUrl = $role === 'admin'
            ? '/admin/transaksi/struk/'.$transaksiId
            : '/kasir/transaksi/struk/'.$transaksiId;

        return redirect($strukUrl)->with('success', 'Transaksi berhasil disimpan.');
    }

    public function adminStruk($id)
    {
        return $this->showStruk((int) $id, 'admin');
    }

    public function kasirStruk($id)
    {
        return $this->showStruk((int) $id, 'kasir');
    }

    public function adminPdf($id)
    {
        return $this->downloadPdf((int) $id, 'admin');
    }

    public function kasirPdf($id)
    {
        return $this->downloadPdf((int) $id, 'kasir');
    }

    private function showStruk(int $id, string $role)
    {
        $transaksi = $this->findTransaksiArray($id);

        $backUrl = $role === 'admin' ? '/admin/transaksi' : '/kasir/transaksi';

        if (! $transaksi) {
            return redirect($backUrl)->with('error', 'Transaksi tidak ditemukan.');
        }

        if ($role === 'kasir' && (int) $transaksi['id_user'] !== (int) auth()->id()) {
            return redirect($backUrl)->with('error', 'Kamu tidak punya akses ke struk ini.');
        }

        $items = $this->itemsWithBarang($id);

        $viewName = $role === 'admin' ? 'admin.transaksi.struk' : 'kasir.transaksi.struk';

        return view($viewName, [
            'title' => 'Struk Transaksi',
            'activeMenu' => 'transaksi',
            'user' => current_user_array(),
            'transaksi' => $transaksi,
            'items' => $items,
            'detailTransaksi' => $items,
        ]);
    }

    private function downloadPdf(int $id, string $role)
    {
        $transaksi = $this->findTransaksiArray($id);

        $backUrl = $role === 'admin' ? '/admin/transaksi' : '/kasir/transaksi';

        if (! $transaksi) {
            return redirect($backUrl)->with('error', 'Transaksi tidak ditemukan.');
        }

        if ($role === 'kasir' && (int) $transaksi['id_user'] !== (int) auth()->id()) {
            return redirect($backUrl)->with('error', 'Kamu tidak punya akses ke struk ini.');
        }

        $items = $this->itemsWithBarang($id);

        $pdf = Pdf::loadView('shared.struk-pdf', [
            'title' => 'Struk PDF',
            'transaksi' => $transaksi,
            'items' => $items,
            'detailTransaksi' => $items,
            'user' => current_user_array(),
        ])->setPaper([0, 0, 226.77, 600], 'portrait');

        return $pdf->download('struk-'.($transaksi['kode_transaksi'] ?? $id).'.pdf');
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

    private function itemsWithBarang(int $transaksiId): array
    {
        return DB::table('detail_transaksi as dt')
            ->join('barang as b', 'b.id', '=', 'dt.id_barang')
            ->select(
                'dt.*',
                'b.kode_barang',
                'b.barcode',
                'b.nama as nama_barang',
                'b.satuan'
            )
            ->where('dt.id_transaksi', $transaksiId)
            ->orderBy('dt.id')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function normalizeCart(string $cartJson): array
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

    private function validateInput(array $items, string $metodeBayar, string $nominalBayar): array
    {
        $errors = [];

        if (empty($items)) {
            $errors[] = 'Keranjang masih kosong.';
        }

        if (! Transaksi::isValidPaymentMethod($metodeBayar)) {
            $errors[] = 'Metode pembayaran tidak valid.';
        }

        if ($metodeBayar === 'cash') {
            if ($nominalBayar === '' || ! is_numeric($nominalBayar)) {
                $errors[] = 'Nominal bayar wajib diisi untuk pembayaran cash.';
            } elseif ((float) $nominalBayar <= 0) {
                $errors[] = 'Nominal bayar harus lebih dari 0.';
            }
        }

        return $errors;
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
                throw new \RuntimeException('Ada barang yang tidak ditemukan atau nonaktif.');
            }

            if ((int) $barang->stok < $qty) {
                throw new \RuntimeException('Stok tidak cukup untuk barang: '.$barang->nama);
            }

            $hargaJual = (float) $barang->harga_jual;
            $hargaBeli = $this->getLastHargaBeli($idBarang);

            $subtotalJual = $hargaJual * $qty;
            $subtotalBeli = $hargaBeli * $qty;
            $labaItem = $subtotalJual - $subtotalBeli;

            $prepared[] = [
                'id_barang' => $idBarang,
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

    private function activeBarangs(): array
    {
        return DB::table('barang as b')
            ->join('kategori as k', 'k.id', '=', 'b.id_kategori')
            ->select('b.*', 'k.nama as nama_kategori')
            ->where('b.status', 'aktif')
            ->orderBy('b.nama')
            ->limit(200)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function paymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
            'ewallet' => 'E-Wallet',
        ];
    }
}
