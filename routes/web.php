<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect('/login');
    }

    $role = auth()->user()->role;

    if ($role === 'admin') {
        return redirect('/admin/dashboard');
    }

    if ($role === 'kasir') {
        return redirect('/kasir/dashboard');
    }

    auth()->logout();

    return redirect('/login');
});

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Error pages
|--------------------------------------------------------------------------
*/

Route::get('/403', fn () => abort(403, 'Akses ditolak.'));

/*
|--------------------------------------------------------------------------
| Admin (role: admin)
|--------------------------------------------------------------------------
*/

Route::middleware('role:admin')->prefix('admin')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'admin']);

    // Kategori
    Route::get('/kategori', [KategoriController::class, 'index']);
    Route::get('/kategori/create', [KategoriController::class, 'create']);
    Route::post('/kategori/store', [KategoriController::class, 'store']);
    Route::get('/kategori/edit/{id}', [KategoriController::class, 'edit']);
    Route::post('/kategori/update/{id}', [KategoriController::class, 'update']);
    Route::post('/kategori/delete/{id}', [KategoriController::class, 'delete']);
    Route::post('/kategori/toggle/{id}', [KategoriController::class, 'toggleStatus']);

    // Barang
    Route::get('/barang', [BarangController::class, 'index']);
    Route::get('/barang/generate-barcode', [BarangController::class, 'generateBarcodeAjax']);
    Route::get('/barang/label/{id}', [BarangController::class, 'label']);
    Route::match(['GET', 'POST'], '/barang/label-bulk', [BarangController::class, 'labelBulk']);
    Route::get('/barang/create', [BarangController::class, 'create']);
    Route::post('/barang/store', [BarangController::class, 'store']);
    Route::get('/barang/edit/{id}', [BarangController::class, 'edit']);
    Route::post('/barang/update/{id}', [BarangController::class, 'update']);
    Route::post('/barang/delete/{id}', [BarangController::class, 'delete']);
    Route::post('/barang/toggle/{id}', [BarangController::class, 'toggleStatus']);

    // Supplier
    Route::get('/supplier', [SupplierController::class, 'index']);
    Route::get('/supplier/create', [SupplierController::class, 'create']);
    Route::post('/supplier/store', [SupplierController::class, 'store']);
    Route::get('/supplier/edit/{id}', [SupplierController::class, 'edit']);
    Route::post('/supplier/update/{id}', [SupplierController::class, 'update']);
    Route::post('/supplier/delete/{id}', [SupplierController::class, 'delete']);
    Route::post('/supplier/toggle/{id}', [SupplierController::class, 'toggleStatus']);

    // Restock
    Route::get('/restock', [RestockController::class, 'index']);
    Route::get('/restock/create', [RestockController::class, 'create']);
    Route::post('/restock/store', [RestockController::class, 'store']);

    // User kasir
    Route::get('/user', [UserController::class, 'index']);
    Route::get('/user/create', [UserController::class, 'create']);
    Route::post('/user/store', [UserController::class, 'store']);
    Route::get('/user/edit/{id}', [UserController::class, 'edit']);
    Route::post('/user/update/{id}', [UserController::class, 'update']);
    Route::get('/user/reset-password/{id}', [UserController::class, 'resetPassword']);
    Route::post('/user/reset-password/{id}', [UserController::class, 'updatePassword']);
    Route::post('/user/delete/{id}', [UserController::class, 'delete']);
    Route::post('/user/toggle/{id}', [UserController::class, 'toggleStatus']);

    // Transaksi
    Route::get('/transaksi', [TransaksiController::class, 'adminIndex']);
    Route::get('/transaksi/struk/{id}', [TransaksiController::class, 'adminStruk']);
    Route::get('/transaksi/pdf/{id}', [TransaksiController::class, 'adminPdf']);

    // Riwayat
    Route::get('/riwayat-transaksi', [RiwayatController::class, 'index']);
    Route::get('/riwayat-transaksi/detail/{id}', [RiwayatController::class, 'detail']);
    Route::get('/riwayat-transaksi/edit/{id}', [RiwayatController::class, 'edit']);
    Route::post('/riwayat-transaksi/update/{id}', [RiwayatController::class, 'update']);
    Route::post('/riwayat-transaksi/cancel/{id}', [RiwayatController::class, 'cancel']);

    // Laporan
    Route::get('/laporan', [LaporanController::class, 'index']);
    Route::get('/laporan/penjualan', [LaporanController::class, 'penjualan']);
    Route::get('/laporan/laba', [LaporanController::class, 'laba']);
    Route::get('/laporan/barang-terlaris', [LaporanController::class, 'barangTerlaris']);
    Route::get('/laporan/restock', [LaporanController::class, 'restock']);

    // Export laporan (Excel)
    Route::get('/laporan/export/ringkasan', [LaporanController::class, 'exportRingkasan']);
    Route::get('/laporan/export/penjualan', [LaporanController::class, 'exportPenjualan']);
    Route::get('/laporan/export/laba', [LaporanController::class, 'exportLaba']);
    Route::get('/laporan/export/barang-terlaris', [LaporanController::class, 'exportBarangTerlaris']);
    Route::get('/laporan/export/restock', [LaporanController::class, 'exportRestock']);
});

/*
|--------------------------------------------------------------------------
| Kasir (role: kasir)
|--------------------------------------------------------------------------
*/

Route::middleware('role:kasir')->prefix('kasir')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'kasir']);

    Route::get('/profil', [ProfilController::class, 'index']);
    Route::post('/profil/update', [ProfilController::class, 'update']);
    Route::post('/profil/password', [ProfilController::class, 'updatePassword']);

    Route::get('/transaksi', [TransaksiController::class, 'kasirIndex']);
    Route::get('/transaksi/struk/{id}', [TransaksiController::class, 'kasirStruk']);
    Route::get('/transaksi/pdf/{id}', [TransaksiController::class, 'kasirPdf']);
});

/*
|--------------------------------------------------------------------------
| Transaksi store (admin & kasir)
|--------------------------------------------------------------------------
*/

Route::post('/transaksi/store', [TransaksiController::class, 'store'])
    ->middleware('role:admin,kasir');
