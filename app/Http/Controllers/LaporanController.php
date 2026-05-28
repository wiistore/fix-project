<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $f = $this->dateFilter($request);

        return view('admin.laporan.index', [
            'title' => 'Laporan',
            'activeMenu' => 'laporan',
            'user' => current_user_array(),
            'tanggalMulai' => $f['tanggal_mulai'],
            'tanggalSelesai' => $f['tanggal_selesai'],
            'summary' => $this->summary($f),
            'penjualanHarian' => $this->penjualanHarian($f),
            'barangTerlaris' => $this->getBarangTerlaris($f, 10),
            'metodePembayaran' => $this->metodePembayaran($f),
            'stokMenipis' => $this->stokMenipis(),
            'transaksiBatal' => $this->transaksiBatal($f),
            'batalPerHari' => $this->batalPerHari($f),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }

    public function penjualan(Request $request)
    {
        $f = $this->dateFilter($request);

        return view('admin.laporan.penjualan', [
            'title' => 'Laporan Penjualan',
            'activeMenu' => 'laporan',
            'user' => current_user_array(),
            'tanggalMulai' => $f['tanggal_mulai'],
            'tanggalSelesai' => $f['tanggal_selesai'],
            'summary' => $this->summary($f),
            'penjualanHarian' => $this->penjualanHarian($f),
            'penjualanKasir' => $this->penjualanByKasir($f),
            'metodePembayaran' => $this->metodePembayaran($f),
        ]);
    }

    public function laba(Request $request)
    {
        $f = $this->dateFilter($request);

        return view('admin.laporan.laba', [
            'title' => 'Laporan Laba',
            'activeMenu' => 'laporan',
            'user' => current_user_array(),
            'tanggalMulai' => $f['tanggal_mulai'],
            'tanggalSelesai' => $f['tanggal_selesai'],
            'summary' => $this->summary($f),
            'penjualanHarian' => $this->penjualanHarian($f),
            'penjualanKasir' => $this->penjualanByKasir($f),
            'barangTerlaris' => $this->getBarangTerlaris($f, 20),
        ]);
    }

    public function barangTerlaris(Request $request)
    {
        $f = $this->dateFilter($request);

        return view('admin.laporan.barang-terlaris', [
            'title' => 'Laporan Barang Terlaris',
            'activeMenu' => 'laporan',
            'user' => current_user_array(),
            'tanggalMulai' => $f['tanggal_mulai'],
            'tanggalSelesai' => $f['tanggal_selesai'],
            'barangTerlaris' => $this->getBarangTerlaris($f, 100),
        ]);
    }

    public function restock(Request $request)
    {
        $f = $this->dateFilter($request);

        return view('admin.laporan.restock', [
            'title' => 'Laporan Restock',
            'activeMenu' => 'laporan',
            'user' => current_user_array(),
            'tanggalMulai' => $f['tanggal_mulai'],
            'tanggalSelesai' => $f['tanggal_selesai'],
            'summary' => $this->restockSummary($f),
            'restockByBarang' => $this->restockByBarang($f),
            'restockBySupplier' => $this->restockBySupplier($f),
        ]);
    }

    /* ========================= Export Excel ========================= */

    public function exportRingkasan(Request $request)
    {
        $f = $this->dateFilter($request);

        return $this->downloadExcel('laporan-ringkasan', function () use ($f) {
            $this->renderSummaryTable($this->summary($f));
            echo '<br>';
            $this->renderPenjualanHarian($this->penjualanHarian($f));
            echo '<br>';
            $this->renderMetodePembayaran($this->metodePembayaran($f));
            echo '<br>';
            $this->renderBarangTerlaris($this->getBarangTerlaris($f, 100));
            echo '<br>';
            $this->renderStokMenipis($this->stokMenipis());
        });
    }

    public function exportPenjualan(Request $request)
    {
        $f = $this->dateFilter($request);

        return $this->downloadExcel('laporan-penjualan', function () use ($f) {
            $this->renderSummaryTable($this->summary($f));
            echo '<br>';
            $this->renderPenjualanHarian($this->penjualanHarian($f));
            echo '<br>';
            $this->renderPenjualanKasir($this->penjualanByKasir($f));
            echo '<br>';
            $this->renderMetodePembayaran($this->metodePembayaran($f));
        });
    }

    public function exportLaba(Request $request)
    {
        $f = $this->dateFilter($request);

        return $this->downloadExcel('laporan-laba', function () use ($f) {
            $this->renderSummaryTable($this->summary($f));
            echo '<br>';
            $this->renderPenjualanHarian($this->penjualanHarian($f));
            echo '<br>';
            $this->renderBarangTerlaris($this->getBarangTerlaris($f, 100));
        });
    }

    public function exportBarangTerlaris(Request $request)
    {
        $f = $this->dateFilter($request);

        return $this->downloadExcel('laporan-barang-terlaris', function () use ($f) {
            $this->renderBarangTerlaris($this->getBarangTerlaris($f, 100));
        });
    }

    public function exportRestock(Request $request)
    {
        $f = $this->dateFilter($request);

        return $this->downloadExcel('laporan-restock', function () use ($f) {
            $summary = $this->restockSummary($f);
            echo '<h2>Ringkasan Restock</h2>';
            echo '<table border="1"><tr><th>Total Restock</th><th>Total Qty</th><th>Total Nilai</th></tr>';
            echo '<tr><td>'.app_e($summary['total_restock']).'</td><td>'.app_e($summary['total_qty']).'</td><td>'.app_e($summary['total_nilai']).'</td></tr></table>';

            echo '<br>';
            $this->renderRestockByBarang($this->restockByBarang($f));
            echo '<br>';
            $this->renderRestockBySupplier($this->restockBySupplier($f));
        });
    }

    /* ========================= Data builders ========================= */

    private function summary(array $f): array
    {
        $row = $this->applyDate(
            DB::table('transaksi as t')->where('t.status', '!=', 'dibatalkan'),
            $f, 't.tanggal'
        )->selectRaw('
            COUNT(t.id) as total_transaksi,
            COALESCE(SUM(t.total_jual), 0) as total_penjualan,
            COALESCE(SUM(t.total_beli), 0) as total_modal,
            COALESCE(SUM(t.total_laba), 0) as total_laba
        ')->first();

        return [
            'total_transaksi' => (int) ($row->total_transaksi ?? 0),
            'total_penjualan' => (float) ($row->total_penjualan ?? 0),
            'total_modal' => (float) ($row->total_modal ?? 0),
            'total_laba' => (float) ($row->total_laba ?? 0),
        ];
    }

    private function penjualanHarian(array $f): array
    {
        return $this->applyDate(
            DB::table('transaksi as t')->where('t.status', '!=', 'dibatalkan'),
            $f, 't.tanggal'
        )->selectRaw('
            DATE(t.tanggal) as tanggal,
            COUNT(t.id) as total_transaksi,
            COALESCE(SUM(t.total_jual), 0) as total_penjualan,
            COALESCE(SUM(t.total_beli), 0) as total_modal,
            COALESCE(SUM(t.total_laba), 0) as total_laba
        ')->groupByRaw('DATE(t.tanggal)')
            ->orderBy('tanggal')
            ->limit(100)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function penjualanByKasir(array $f): array
    {
        return $this->applyDate(
            DB::table('transaksi as t')
                ->join('users as u', 'u.id', '=', 't.id_user')
                ->where('t.status', '!=', 'dibatalkan'),
            $f, 't.tanggal'
        )->selectRaw('
            u.id as id_user,
            u.username as nama_kasir,
            COUNT(t.id) as total_transaksi,
            COALESCE(SUM(t.total_jual), 0) as total_penjualan,
            COALESCE(SUM(t.total_beli), 0) as total_modal,
            COALESCE(SUM(t.total_laba), 0) as total_laba
        ')->groupBy('u.id', 'u.username')
            ->orderByDesc('total_penjualan')
            ->limit(100)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function getBarangTerlaris(array $f, int $limit): array
    {
        return $this->applyDate(
            DB::table('detail_transaksi as dt')
                ->join('transaksi as t', 't.id', '=', 'dt.id_transaksi')
                ->join('barang as b', 'b.id', '=', 'dt.id_barang')
                ->join('kategori as k', 'k.id', '=', 'b.id_kategori')
                ->where('t.status', '!=', 'dibatalkan'),
            $f, 't.tanggal'
        )->selectRaw('
            b.id, b.kode_barang, b.barcode,
            b.nama as nama_barang,
            b.satuan,
            k.nama as nama_kategori,
            COALESCE(SUM(dt.qty), 0) as total_qty,
            COALESCE(SUM(dt.subtotal_jual), 0) as total_penjualan,
            COALESCE(SUM(dt.subtotal_beli), 0) as total_modal,
            COALESCE(SUM(dt.laba_item), 0) as total_laba
        ')->groupBy('b.id', 'b.kode_barang', 'b.barcode', 'b.nama', 'b.satuan', 'k.nama')
            ->orderByDesc('total_qty')
            ->orderByDesc('total_penjualan')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function metodePembayaran(array $f): array
    {
        return $this->applyDate(
            DB::table('transaksi as t')->where('t.status', '!=', 'dibatalkan'),
            $f, 't.tanggal'
        )->selectRaw('
            t.metode_bayar,
            COUNT(t.id) as total_transaksi,
            COALESCE(SUM(t.total_jual), 0) as total_penjualan
        ')->groupBy('t.metode_bayar')
            ->orderByDesc('total_penjualan')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function restockSummary(array $f): array
    {
        $row = $this->applyDate(DB::table('restock as r'), $f, 'r.tanggal')
            ->selectRaw('
                COUNT(r.id) as total_restock,
                COALESCE(SUM(r.qty), 0) as total_qty,
                COALESCE(SUM(r.total_nilai), 0) as total_nilai
            ')->first();

        return [
            'total_restock' => (int) ($row->total_restock ?? 0),
            'total_qty' => (int) ($row->total_qty ?? 0),
            'total_nilai' => (float) ($row->total_nilai ?? 0),
        ];
    }

    private function restockByBarang(array $f): array
    {
        return $this->applyDate(
            DB::table('restock as r')->join('barang as b', 'b.id', '=', 'r.id_barang'),
            $f, 'r.tanggal'
        )->selectRaw('
            b.id, b.kode_barang, b.nama as nama_barang, b.satuan,
            COALESCE(SUM(r.qty), 0) as total_qty,
            COALESCE(SUM(r.total_nilai), 0) as total_nilai,
            AVG(r.harga_beli) as rata_harga_beli
        ')->groupBy('b.id', 'b.kode_barang', 'b.nama', 'b.satuan')
            ->orderByDesc('total_nilai')
            ->limit(100)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function restockBySupplier(array $f): array
    {
        return $this->applyDate(
            DB::table('restock as r')->join('supplier as s', 's.id', '=', 'r.id_supplier'),
            $f, 'r.tanggal'
        )->selectRaw('
            s.id, s.nama as nama_supplier,
            COUNT(r.id) as total_restock,
            COALESCE(SUM(r.qty), 0) as total_qty,
            COALESCE(SUM(r.total_nilai), 0) as total_nilai
        ')->groupBy('s.id', 's.nama')
            ->orderByDesc('total_nilai')
            ->limit(100)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function stokMenipis(): array
    {
        return DB::table('barang as b')
            ->join('kategori as k', 'k.id', '=', 'b.id_kategori')
            ->select('b.id', 'b.kode_barang', 'b.nama as nama_barang', 'b.stok', 'b.stok_minimum', 'b.satuan', 'k.nama as nama_kategori')
            ->where('b.status', 'aktif')
            ->whereColumn('b.stok', '<=', 'b.stok_minimum')
            ->orderBy('b.stok')
            ->orderBy('b.nama')
            ->limit(100)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function transaksiBatal(array $f): array
    {
        return $this->applyDate(
            DB::table('transaksi as t')
                ->join('users as u', 'u.id', '=', 't.id_user')
                ->where('t.status', 'dibatalkan'),
            $f, 't.tanggal'
        )->select('t.id', 't.kode_transaksi', 't.tanggal', 't.total_jual', 't.alasan_batal', 'u.username as nama_kasir')
            ->orderByDesc('t.tanggal')
            ->limit(50)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function batalPerHari(array $f): array
    {
        return $this->applyDate(
            DB::table('transaksi as t')->where('t.status', 'dibatalkan'),
            $f, 't.tanggal'
        )->selectRaw('DATE(t.tanggal) as tanggal, COUNT(t.id) as total_batal')
            ->groupByRaw('DATE(t.tanggal)')
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /* ========================= Helpers ========================= */

    private function applyDate($query, array $f, string $column)
    {
        if (! empty($f['tanggal_mulai'])) {
            $query->whereDate($column, '>=', $f['tanggal_mulai']);
        }
        if (! empty($f['tanggal_selesai'])) {
            $query->whereDate($column, '<=', $f['tanggal_selesai']);
        }

        return $query;
    }

    private function dateFilter(Request $request): array
    {
        $start = trim((string) $request->query('tanggal_mulai', ''));
        $end = trim((string) $request->query('tanggal_selesai', ''));

        if ($start !== '' && ! \DateTime::createFromFormat('Y-m-d', $start)) {
            $start = '';
        }
        if ($end !== '' && ! \DateTime::createFromFormat('Y-m-d', $end)) {
            $end = '';
        }

        if ($start !== '' && $end !== '' && $start > $end) {
            [$start, $end] = [$end, $start];
        }

        return ['tanggal_mulai' => $start, 'tanggal_selesai' => $end];
    }

    /* ========================= Excel renderers ========================= */

    private function downloadExcel(string $filename, callable $content)
    {
        $safe = preg_replace('/[^a-zA-Z0-9-_]/', '-', $filename);
        $name = $safe.'-'.date('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($content) {
            echo '<html><head><meta charset="UTF-8"><style>
                body{font-family:Arial,sans-serif;}
                table{border-collapse:collapse;margin-bottom:16px;}
                th{background:#116530;color:#ffffff;font-weight:bold;}
                th,td{padding:8px;border:1px solid #999999;}
                h2{color:#116530;}
            </style></head><body>';

            $content();

            echo '</body></html>';
        }, $name, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function renderSummaryTable(array $s): void
    {
        echo '<h2>Ringkasan</h2>';
        echo '<table border="1"><tr><th>Total Transaksi</th><th>Total Penjualan</th><th>Total Modal</th><th>Total Laba</th></tr>';
        echo '<tr><td>'.app_e($s['total_transaksi']).'</td>';
        echo '<td>'.app_e($s['total_penjualan']).'</td>';
        echo '<td>'.app_e($s['total_modal']).'</td>';
        echo '<td>'.app_e($s['total_laba']).'</td></tr></table>';
    }

    private function renderPenjualanHarian(array $rows): void
    {
        echo '<h2>Penjualan Harian</h2>';
        echo '<table border="1"><tr><th>Tanggal</th><th>Total Transaksi</th><th>Total Penjualan</th><th>Total Modal</th><th>Total Laba</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>'.app_e($r['tanggal'] ?? '-').'</td>';
            echo '<td>'.app_e($r['total_transaksi'] ?? 0).'</td>';
            echo '<td>'.app_e($r['total_penjualan'] ?? 0).'</td>';
            echo '<td>'.app_e($r['total_modal'] ?? 0).'</td>';
            echo '<td>'.app_e($r['total_laba'] ?? 0).'</td></tr>';
        }
        echo '</table>';
    }

    private function renderPenjualanKasir(array $rows): void
    {
        echo '<h2>Penjualan Per Kasir</h2>';
        echo '<table border="1"><tr><th>Kasir</th><th>Total Transaksi</th><th>Total Penjualan</th><th>Total Modal</th><th>Total Laba</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>'.app_e($r['nama_kasir'] ?? '-').'</td>';
            echo '<td>'.app_e($r['total_transaksi'] ?? 0).'</td>';
            echo '<td>'.app_e($r['total_penjualan'] ?? 0).'</td>';
            echo '<td>'.app_e($r['total_modal'] ?? 0).'</td>';
            echo '<td>'.app_e($r['total_laba'] ?? 0).'</td></tr>';
        }
        echo '</table>';
    }

    private function renderMetodePembayaran(array $rows): void
    {
        echo '<h2>Metode Pembayaran</h2>';
        echo '<table border="1"><tr><th>Metode</th><th>Total Transaksi</th><th>Total Penjualan</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>'.app_e(strtoupper($r['metode_bayar'] ?? '-')).'</td>';
            echo '<td>'.app_e($r['total_transaksi'] ?? 0).'</td>';
            echo '<td>'.app_e($r['total_penjualan'] ?? 0).'</td></tr>';
        }
        echo '</table>';
    }

    private function renderBarangTerlaris(array $rows): void
    {
        echo '<h2>Barang Terlaris</h2>';
        echo '<table border="1"><tr><th>No</th><th>Kode</th><th>Barcode</th><th>Nama</th><th>Kategori</th><th>Qty</th><th>Penjualan</th><th>Modal</th><th>Laba</th></tr>';
        foreach ($rows as $i => $r) {
            echo '<tr><td>'.app_e($i + 1).'</td>';
            echo '<td>'.app_e($r['kode_barang'] ?? '-').'</td>';
            echo '<td>'.app_e($r['barcode'] ?? '-').'</td>';
            echo '<td>'.app_e($r['nama_barang'] ?? '-').'</td>';
            echo '<td>'.app_e($r['nama_kategori'] ?? '-').'</td>';
            echo '<td>'.app_e($r['total_qty'] ?? 0).' '.app_e($r['satuan'] ?? '').'</td>';
            echo '<td>'.app_e($r['total_penjualan'] ?? 0).'</td>';
            echo '<td>'.app_e($r['total_modal'] ?? 0).'</td>';
            echo '<td>'.app_e($r['total_laba'] ?? 0).'</td></tr>';
        }
        echo '</table>';
    }

    private function renderStokMenipis(array $rows): void
    {
        echo '<h2>Stok Menipis</h2>';
        echo '<table border="1"><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Stok</th><th>Min</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>'.app_e($r['kode_barang'] ?? '-').'</td>';
            echo '<td>'.app_e($r['nama_barang'] ?? '-').'</td>';
            echo '<td>'.app_e($r['nama_kategori'] ?? '-').'</td>';
            echo '<td>'.app_e($r['stok'] ?? 0).' '.app_e($r['satuan'] ?? '').'</td>';
            echo '<td>'.app_e($r['stok_minimum'] ?? 0).'</td></tr>';
        }
        echo '</table>';
    }

    private function renderRestockByBarang(array $rows): void
    {
        echo '<h2>Restock Per Barang</h2>';
        echo '<table border="1"><tr><th>Kode</th><th>Nama</th><th>Qty</th><th>Total Nilai</th><th>Rata Harga Beli</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>'.app_e($r['kode_barang'] ?? '-').'</td>';
            echo '<td>'.app_e($r['nama_barang'] ?? '-').'</td>';
            echo '<td>'.app_e($r['total_qty'] ?? 0).' '.app_e($r['satuan'] ?? '').'</td>';
            echo '<td>'.app_e($r['total_nilai'] ?? 0).'</td>';
            echo '<td>'.app_e($r['rata_harga_beli'] ?? 0).'</td></tr>';
        }
        echo '</table>';
    }

    private function renderRestockBySupplier(array $rows): void
    {
        echo '<h2>Restock Per Supplier</h2>';
        echo '<table border="1"><tr><th>Supplier</th><th>Total Restock</th><th>Total Qty</th><th>Total Nilai</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>'.app_e($r['nama_supplier'] ?? '-').'</td>';
            echo '<td>'.app_e($r['total_restock'] ?? 0).'</td>';
            echo '<td>'.app_e($r['total_qty'] ?? 0).'</td>';
            echo '<td>'.app_e($r['total_nilai'] ?? 0).'</td></tr>';
        }
        echo '</table>';
    }
}
