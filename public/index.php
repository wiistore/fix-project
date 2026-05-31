<?php

declare(strict_types=1);

if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

// Load config
require_once dirname(__DIR__) . '/app/config/config.php';

// Load core
require_once APP_PATH . '/core/Session.php';
require_once APP_PATH . '/core/Security.php';
require_once APP_PATH . '/core/Response.php';
require_once APP_PATH . '/core/Validator.php';
require_once APP_PATH . '/core/Model.php';
require_once APP_PATH . '/core/Controller.php';
require_once APP_PATH . '/core/Router.php';

// Load middleware kalau ada
foreach (glob(APP_PATH . '/middleware/*.php') ?: [] as $middlewareFile) {
    require_once $middlewareFile;
}

// Mulai session
Session::start();

// Init router
$router = new Router();

/*
|--------------------------------------------------------------------------
| Root
|--------------------------------------------------------------------------
*/

$router->get('/', function (): void {
    if (!Session::isLoggedIn()) {
        Response::redirect('/login');
    }

    $role = Session::role();

    if ($role === 'admin') {
        Response::redirect('/admin/dashboard');
    }

    if ($role === 'kasir') {
        Response::redirect('/kasir/dashboard');
    }

    Session::logout();
    Response::redirect('/login');
});

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

$router->get('/admin/dashboard', 'AdminController@dashboard');
$router->get('/kasir/dashboard', 'KasirController@dashboard');

/*
|--------------------------------------------------------------------------
| Profil kasir
|--------------------------------------------------------------------------
*/

$router->get('/kasir/profil', 'KasirController@profil');
$router->post('/kasir/profil/password', 'KasirController@updatePassword');

/*
|--------------------------------------------------------------------------
| Kategori
|--------------------------------------------------------------------------
*/

$router->get('/admin/kategori', 'KategoriController@index');
$router->get('/admin/kategori/create', 'KategoriController@create');
$router->post('/admin/kategori/store', 'KategoriController@store');
$router->get('/admin/kategori/edit/{id}', 'KategoriController@edit');
$router->post('/admin/kategori/update/{id}', 'KategoriController@update');
$router->post('/admin/kategori/delete/{id}', 'KategoriController@delete');

/*
|--------------------------------------------------------------------------
| Barang
|--------------------------------------------------------------------------
*/

$router->get('/admin/barang', 'BarangController@index');
$router->get('/admin/barang/generate-barcode', 'BarangController@generateBarcodeAjax');
$router->get('/admin/barang/label/{id}', 'BarangController@label');
$router->get('/admin/barang/label-bulk', 'BarangController@labelBulk');
$router->post('/admin/barang/label-bulk', 'BarangController@labelBulk');
$router->get('/admin/barang/create', 'BarangController@create');
$router->post('/admin/barang/store', 'BarangController@store');
$router->get('/admin/barang/edit/{id}', 'BarangController@edit');
$router->post('/admin/barang/update/{id}', 'BarangController@update');
$router->post('/admin/barang/delete/{id}', 'BarangController@delete');
$router->post('/admin/barang/toggle/{id}', 'BarangController@toggleStatus');

/*
|--------------------------------------------------------------------------
| Supplier
|--------------------------------------------------------------------------
*/

$router->get('/admin/supplier', 'SupplierController@index');
$router->get('/admin/supplier/create', 'SupplierController@create');
$router->post('/admin/supplier/store', 'SupplierController@store');
$router->get('/admin/supplier/edit/{id}', 'SupplierController@edit');
$router->post('/admin/supplier/update/{id}', 'SupplierController@update');
$router->post('/admin/supplier/delete/{id}', 'SupplierController@delete');
$router->post('/admin/supplier/toggle/{id}', 'SupplierController@toggleStatus');

/*
|--------------------------------------------------------------------------
| Restock
|--------------------------------------------------------------------------
*/

$router->get('/admin/restock', 'RestockController@index');
$router->get('/admin/restock/create', 'RestockController@create');
$router->post('/admin/restock/store', 'RestockController@store');

/*
|--------------------------------------------------------------------------
| User kasir
|--------------------------------------------------------------------------
*/

$router->get('/admin/user', 'UserController@index');
$router->get('/admin/user/create', 'UserController@create');
$router->post('/admin/user/store', 'UserController@store');
$router->get('/admin/user/edit/{id}', 'UserController@edit');
$router->post('/admin/user/update/{id}', 'UserController@update');
$router->get('/admin/user/reset-password/{id}', 'UserController@resetPassword');
$router->post('/admin/user/reset-password/{id}', 'UserController@updatePassword');
$router->post('/admin/user/delete/{id}', 'UserController@delete');
$router->post('/admin/user/toggle/{id}', 'UserController@toggleStatus');

/* |--------------------------------------------------------------------------
| Transaksi Routes
|-------------------------------------------------------------------------- */
$router->get('/admin/transaksi', 'TransaksiController@adminIndex');
$router->get('/kasir/transaksi', 'TransaksiController@kasirIndex');
$router->post('/transaksi/store', 'TransaksiController@store');

$router->get('/admin/transaksi/struk/{id}', 'TransaksiController@adminStruk');
$router->get('/kasir/transaksi/struk/{id}', 'TransaksiController@kasirStruk');

$router->get('/admin/transaksi/pdf/{id}', 'TransaksiController@adminPdf');
$router->get('/kasir/transaksi/pdf/{id}', 'TransaksiController@kasirPdf');
/*
|--------------------------------------------------------------------------
| Riwayat transaksi admin
|--------------------------------------------------------------------------
*/

$router->get('/admin/riwayat-transaksi', 'RiwayatController@adminIndex');
$router->get('/admin/riwayat-transaksi/detail/{id}', 'RiwayatController@adminDetail');
$router->get('/admin/riwayat-transaksi/edit/{id}', 'RiwayatController@edit');
$router->post('/admin/riwayat-transaksi/update/{id}', 'RiwayatController@update');
$router->post('/admin/riwayat-transaksi/cancel/{id}', 'RiwayatController@cancel');

/* |--------------------------------------------------------------------------
| Laporan Routes
|-------------------------------------------------------------------------- */
$router->get('/admin/laporan', 'LaporanController@index');
$router->get('/admin/laporan/penjualan', 'LaporanController@penjualan');
$router->get('/admin/laporan/laba', 'LaporanController@laba');
$router->get('/admin/laporan/barang-terlaris', 'LaporanController@barangTerlaris');
$router->get('/admin/laporan/restock', 'LaporanController@restock');

// Export laporan
$router->get('/admin/laporan/export/ringkasan', 'LaporanController@exportRingkasan');
$router->get('/admin/laporan/export/penjualan', 'LaporanController@exportPenjualan');
$router->get('/admin/laporan/export/laba', 'LaporanController@exportLaba');
$router->get('/admin/laporan/export/barang-terlaris', 'LaporanController@exportBarangTerlaris');
$router->get('/admin/laporan/export/restock', 'LaporanController@exportRestock');
/*
|--------------------------------------------------------------------------
| Error
|--------------------------------------------------------------------------
*/

$router->get('/403', function (): void {
    Response::abort(403, 'Akses ditolak.');
});

$router->get('/404', function (): void {
    Response::abort(404, 'Halaman tidak ditemukan.');
});

// Jalankan aplikasi
try {
    $router->run();
} catch (Throwable $error) {
    error_log($error->getMessage());

    $message = APP_DEBUG
        ? $error->getMessage() . ' di ' . $error->getFile() . ':' . $error->getLine()
        : 'Terjadi kesalahan pada server.';

    Response::abort(500, $message);
}