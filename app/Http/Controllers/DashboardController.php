<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function admin()
    {
        $summary = $this->adminSummary();

        return view('admin.dashboard', [
            'title' => 'Dashboard Admin',
            'activeMenu' => 'dashboard',
            'user' => current_user_array(),
            'dashboard' => $summary,
            'totalBarang' => $summary['total_barang'],
            'totalTransaksiHariIni' => $summary['total_transaksi_hari_ini'],
            'totalPenjualanHariIni' => $summary['penjualan_hari_ini'],
            'stokMenipis' => $summary['stok_menipis'],
            'transaksiTerbaru' => $summary['transaksi_terbaru'],
            'chartPenjualan7Hari' => $summary['chart_penjualan_7_hari'],
            'chartTopBarang' => $summary['chart_top_barang'],
            'chartStatusStok' => $summary['chart_status_stok'],
        ]);
    }

    public function kasir()
    {
        $userId = (int) auth()->id();
        $summary = $this->kasirSummary($userId);

        return view('kasir.dashboard', [
            'title' => 'Dashboard Kasir',
            'activeMenu' => 'dashboard',
            'user' => current_user_array(),
            'dashboard' => $summary,
            'totalTransaksiHariIni' => $summary['total_transaksi_hari_ini'],
            'totalPenjualanHariIni' => $summary['penjualan_hari_ini'],
            'totalItemHariIni' => $summary['total_item_hari_ini'],
            'transaksiTerbaru' => $summary['transaksi_terbaru'],
        ]);
    }

    private function adminSummary(): array
    {
        return [
            'total_barang' => (int) DB::table('barang')->count(),
            'total_transaksi_hari_ini' => (int) DB::table('transaksi')
                ->whereDate('tanggal', now()->toDateString())
                ->count(),
            'penjualan_hari_ini' => (float) DB::table('transaksi')
                ->whereDate('tanggal', now()->toDateString())
                ->where('status', '!=', 'dibatalkan')
                ->sum('total_jual'),
            'stok_menipis' => (int) DB::table('barang')
                ->where('status', 'aktif')
                ->whereColumn('stok', '<=', 'stok_minimum')
                ->count(),
            'transaksi_terbaru' => DB::table('transaksi as t')
                ->join('users as u', 'u.id', '=', 't.id_user')
                ->select('t.id', 't.kode_transaksi', 't.tanggal', 't.total_jual', 't.metode_bayar', 'u.username as kasir')
                ->orderByDesc('t.tanggal')
                ->orderByDesc('t.id')
                ->limit(10)
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all(),
            'chart_penjualan_7_hari' => $this->chart7Hari(),
            'chart_top_barang' => $this->chartTopBarang(),
            'chart_status_stok' => $this->chartStatusStok(),
        ];
    }

    private function kasirSummary(int $userId): array
    {
        $today = now()->toDateString();

        $transaksiTerbaru = DB::table('transaksi')
            ->where('id_user', $userId)
            ->select('id', 'kode_transaksi', 'tanggal', 'total_jual', 'metode_bayar')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        return [
            'total_transaksi_hari_ini' => (int) DB::table('transaksi')
                ->where('id_user', $userId)
                ->whereDate('tanggal', $today)
                ->count(),
            'penjualan_hari_ini' => (float) DB::table('transaksi')
                ->where('id_user', $userId)
                ->whereDate('tanggal', $today)
                ->where('status', '!=', 'dibatalkan')
                ->sum('total_jual'),
            'total_item_hari_ini' => (int) DB::table('detail_transaksi as dt')
                ->join('transaksi as t', 't.id', '=', 'dt.id_transaksi')
                ->where('t.id_user', $userId)
                ->whereDate('t.tanggal', $today)
                ->where('t.status', '!=', 'dibatalkan')
                ->sum('dt.qty'),
            'transaksi_terbaru' => $transaksiTerbaru,
        ];
    }

    private function chart7Hari(): array
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[$date] = now()->subDays($i)->format('d M');
            $values[$date] = 0.0;
        }

        $rows = DB::table('transaksi')
            ->selectRaw('DATE(tanggal) as tanggal, COALESCE(SUM(total_jual), 0) as total')
            ->whereDate('tanggal', '>=', now()->subDays(6)->toDateString())
            ->where('status', '!=', 'dibatalkan')
            ->groupByRaw('DATE(tanggal)')
            ->get();

        foreach ($rows as $row) {
            $date = (string) $row->tanggal;
            if (array_key_exists($date, $values)) {
                $values[$date] = (float) $row->total;
            }
        }

        return [
            'labels' => array_values($labels),
            'values' => array_values($values),
        ];
    }

    private function chartTopBarang(): array
    {
        $rows = DB::table('detail_transaksi as dt')
            ->join('barang as b', 'b.id', '=', 'dt.id_barang')
            ->join('transaksi as t', 't.id', '=', 'dt.id_transaksi')
            ->selectRaw('b.nama, COALESCE(SUM(dt.qty), 0) as total_qty')
            ->whereDate('t.tanggal', '>=', now()->subDays(30)->toDateString())
            ->where('t.status', '!=', 'dibatalkan')
            ->groupBy('b.id', 'b.nama')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return [
            'labels' => $rows->pluck('nama')->all(),
            'values' => $rows->pluck('total_qty')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function chartStatusStok(): array
    {
        $row = DB::table('barang')->selectRaw("
            SUM(CASE WHEN status = 'aktif' AND stok > stok_minimum THEN 1 ELSE 0 END) as aman,
            SUM(CASE WHEN status = 'aktif' AND stok > 0 AND stok <= stok_minimum THEN 1 ELSE 0 END) as menipis,
            SUM(CASE WHEN status = 'aktif' AND stok <= 0 THEN 1 ELSE 0 END) as habis
        ")->first();

        return [
            'labels' => ['Aman', 'Menipis', 'Habis'],
            'values' => [
                (int) ($row->aman ?? 0),
                (int) ($row->menipis ?? 0),
                (int) ($row->habis ?? 0),
            ],
        ];
    }
}
