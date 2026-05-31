# Sequence Diagram - Sistem POS

> Sequence Diagram untuk aplikasi kasir berbasis **PHP Native (custom MVC)**.
> Disusun berdasarkan logika kode aktual pada `app/controllers/*` (sinkron dengan branch `main`).
> Format: **Mermaid** (render di GitHub / VS Code / [mermaid.live](https://mermaid.live)).
>
> Sequence Diagram menunjukkan **urutan interaksi antar objek/komponen** secara kronologis:
> Aktor → Router → Middleware → Controller → Model → Database → View → Response.

---

## Daftar Isi

1. [Login](#1-login)
2. [Logout](#2-logout)
3. [Lihat Dashboard Admin](#3-lihat-dashboard-admin)
4. [Transaksi Penjualan (POS)](#4-transaksi-penjualan-pos)
5. [Cetak Struk PDF](#5-cetak-struk-pdf)
6. [Restock Stok Masuk](#6-restock-stok-masuk)
7. [Restock Stok Keluar](#7-restock-stok-keluar)
8. [Tambah Barang](#8-tambah-barang)
9. [Batalkan Transaksi](#9-batalkan-transaksi)
10. [Edit Transaksi](#10-edit-transaksi)
11. [Lihat Laporan + Export Excel](#11-lihat-laporan--export-excel)
12. [Ganti Password Kasir](#12-ganti-password-kasir)

---

## 1. Login

```mermaid
sequenceDiagram
    actor U as User
    participant R as Router
    participant C as AuthController
    participant M as User Model
    participant DB as Database
    participant S as Session
    participant V as View

    U->>R: GET /login
    R->>C: loginForm()
    C->>S: isLoggedIn()?
    alt Sudah login
        S-->>C: true
        C->>C: redirectByRole()
        C-->>U: Redirect /admin atau /kasir dashboard
    else Belum login
        S-->>C: false
        C->>V: render('auth/login')
        V-->>U: Tampilkan form login
    end

    U->>R: POST /login
    R->>C: login()
    C->>C: Validasi input (kosong?)
    alt Input kosong
        C->>S: setFlash('error', 'wajib diisi')
        C-->>U: Redirect /login
    end
    C->>M: findByUsername(username)
    M->>DB: SELECT * FROM users WHERE username = ?
    DB-->>M: result / null
    M-->>C: user data

    alt User tidak ada / password salah
        C->>S: setFlash('error', 'salah')
        C-->>U: Redirect /login
    end

    alt Status nonaktif
        C->>S: setFlash('error', 'nonaktif')
        C-->>U: Redirect /login
    end

    alt Role tidak valid
        C->>S: setFlash('error', 'role invalid')
        C-->>U: Redirect /login
    end

    C->>S: login(user tanpa password)
    C->>C: redirectByRole(role)
    C-->>U: Redirect /admin/dashboard atau /kasir/dashboard
```

---

## 2. Logout

```mermaid
sequenceDiagram
    actor U as User
    participant R as Router
    participant C as AuthController
    participant S as Session

    U->>R: GET /logout
    R->>C: logout()
    C->>S: logout() [destroy session]
    C-->>U: Redirect /login
```

---

## 3. Lihat Dashboard Admin

```mermaid
sequenceDiagram
    actor A as Admin
    participant R as Router
    participant MW as AdminMiddleware
    participant C as AdminController
    participant M as Dashboard Model
    participant DB as Database
    participant V as View

    A->>R: GET /admin/dashboard
    R->>MW: checkRole('admin')
    MW->>MW: Session::role() == 'admin'?
    alt Bukan admin
        MW-->>A: Redirect /login atau 403
    end
    MW->>C: dashboard()
    C->>M: adminSummary()
    M->>DB: Query total barang, penjualan hari ini,<br/>stok menipis, transaksi terbaru
    DB-->>M: result set
    M-->>C: summary data
    C->>M: chartData()
    M->>DB: Query penjualan 7 hari, top barang
    DB-->>M: chart data
    M-->>C: chart data
    C->>V: render('admin/dashboard', data)
    V-->>A: HTML dashboard
```

---

## 4. Transaksi Penjualan (POS)

> Sequence terpanjang — mencakup validasi, DB transaction, hitung harga real-time dari DB.

```mermaid
sequenceDiagram
    actor K as Admin/Kasir
    participant R as Router
    participant C as TransaksiController
    participant BM as Barang Model
    participant TM as Transaksi Model
    participant DM as DetailTransaksi Model
    participant RM as Restock Model
    participant DB as Database
    participant S as Session

    K->>R: POST /transaksi/store
    R->>C: store()
    C->>C: requireRole(['admin','kasir'])

    C->>C: normalizeCart(cart_json)
    C->>C: validateTransactionInput(items, metode, nominal)
    alt Validasi gagal
        C->>S: setFlash('error', ...)
        C-->>K: Redirect kembali ke POS
    end

    C->>DB: beginTransaction()

    loop Setiap item di keranjang
        C->>BM: findActiveById(id_barang)
        BM->>DB: SELECT * FROM barang WHERE id=? AND status='aktif'
        DB-->>BM: barang data
        BM-->>C: barang
        alt Barang tidak ada / nonaktif
            C->>DB: rollBack()
            C-->>K: Flash error + redirect
        end
        alt Stok tidak cukup
            C->>DB: rollBack()
            C-->>K: Flash error + redirect
        end
        C->>RM: getLastHargaBeli(id_barang)
        RM->>DB: SELECT harga_beli FROM restock ORDER BY id DESC LIMIT 1
        DB-->>RM: harga_beli
        RM-->>C: harga_beli
        C->>C: Hitung subtotal_jual, subtotal_beli, laba_item
    end

    C->>C: Hitung total_jual, total_beli, total_laba

    alt Cash dan nominal < total
        C->>DB: rollBack()
        C-->>K: Flash error + redirect
    end

    C->>TM: generateCode()
    TM-->>C: kode_transaksi (TRX-XXXXXX)
    C->>TM: create(transaksi_data)
    TM->>DB: INSERT INTO transaksi (...)
    DB-->>TM: transaksi_id
    TM-->>C: transaksi_id

    loop Setiap item
        C->>DM: create(detail_data)
        DM->>DB: INSERT INTO detail_transaksi (...)
        DB-->>DM: detail_id
        C->>BM: decreaseStock(id_barang, qty)
        BM->>DB: UPDATE barang SET stok = stok - ? WHERE id = ?
        DB-->>BM: success
    end

    C->>DB: commit()
    C->>S: setFlash('success', 'Transaksi berhasil')
    C-->>K: Redirect /struk/{id}
```

---

## 5. Cetak Struk PDF

```mermaid
sequenceDiagram
    actor K as Admin/Kasir
    participant R as Router
    participant C as TransaksiController
    participant TM as Transaksi Model
    participant DM as DetailTransaksi Model
    participant DB as Database
    participant PDF as Dompdf
    participant V as View (struk-pdf.php)

    K->>R: GET /transaksi/pdf/{id}
    R->>C: adminPdf(id) / kasirPdf(id)
    C->>C: requireRole()

    alt Kasir
        C->>TM: findById(id)
        TM->>DB: SELECT * FROM transaksi WHERE id = ?
        DB-->>TM: transaksi
        TM-->>C: transaksi
        C->>C: Cek id_user == Session::userId()
        alt Bukan miliknya
            C-->>K: Flash error + redirect
        end
    end

    C->>TM: findById(id)
    TM-->>C: transaksi
    alt Tidak ditemukan
        C-->>K: Flash error + redirect
    end
    C->>DM: getItemsWithBarang(id)
    DM->>DB: SELECT detail + JOIN barang
    DB-->>DM: items
    DM-->>C: items

    C->>C: class_exists('Dompdf')?
    alt Tidak ada
        C-->>C: throw RuntimeException
    end

    C->>V: ob_start() + require struk-pdf.php
    V-->>C: HTML string
    C->>PDF: new Dompdf()
    C->>PDF: loadHtml(html)
    C->>PDF: setPaper([0,0,226.77,600], 'portrait')
    C->>PDF: render()
    C->>PDF: stream(filename, Attachment=true)
    PDF-->>K: Download file PDF
```

---

## 6. Restock Stok Masuk

```mermaid
sequenceDiagram
    actor A as Admin
    participant R as Router
    participant C as RestockController
    participant BM as Barang Model
    participant SM as Supplier Model
    participant RM as Restock Model
    participant DB as Database
    participant S as Session

    A->>R: POST /admin/restock/store (tipe=masuk)
    R->>C: store()
    C->>C: requireRole('admin')
    C->>C: validatePayload(data)

    C->>BM: findActiveById(id_barang)
    BM->>DB: SELECT FROM barang
    DB-->>BM: barang
    BM-->>C: barang

    C->>SM: findById(id_supplier)
    SM->>DB: SELECT FROM supplier
    DB-->>SM: supplier
    SM-->>C: supplier
    alt Supplier tidak aktif
        C->>S: set errors
        C-->>A: Redirect ke form
    end

    alt Validasi gagal
        C->>S: set errors
        C-->>A: Redirect ke form
    end

    C->>DB: beginTransaction()
    C->>RM: create(restock_data)
    RM->>DB: INSERT INTO restock (...)
    DB-->>RM: restock_id
    C->>BM: increaseStock(id_barang, qty)
    BM->>DB: UPDATE barang SET stok = stok + ?
    DB-->>BM: success

    alt harga_jual_baru diisi
        C->>BM: updateHargaJual(id_barang, harga)
        BM->>DB: UPDATE barang SET harga_jual = ?
        DB-->>BM: success
    end

    C->>DB: commit()
    C->>S: setFlash('success', 'Restock berhasil')
    C-->>A: Redirect /admin/restock
```

---

## 7. Restock Stok Keluar

```mermaid
sequenceDiagram
    actor A as Admin
    participant R as Router
    participant C as RestockController
    participant BM as Barang Model
    participant RM as Restock Model
    participant DB as Database
    participant S as Session

    A->>R: POST /admin/restock/store (tipe=keluar)
    R->>C: store()
    C->>C: requireRole('admin')
    C->>C: validatePayload(data)
    C->>BM: findActiveById(id_barang)
    BM-->>C: barang (stok=N)

    alt qty > stok
        C->>S: set errors
        C-->>A: Redirect ke form
    end

    alt alasan kosong
        C->>S: set errors
        C-->>A: Redirect ke form
    end

    C->>DB: beginTransaction()
    C->>RM: create(restock_data + alasan)
    RM->>DB: INSERT INTO restock (...)
    DB-->>RM: restock_id
    C->>BM: decreaseStock(id_barang, qty)
    BM->>DB: UPDATE barang SET stok = stok - ?
    DB-->>BM: success

    C->>DB: commit()
    C->>S: setFlash('success', 'Stok berhasil dikurangi')
    C-->>A: Redirect /admin/restock
```

---

## 8. Tambah Barang

```mermaid
sequenceDiagram
    actor A as Admin
    participant R as Router
    participant C as BarangController
    participant KM as Kategori Model
    participant BM as Barang Model
    participant DB as Database
    participant V as View
    participant S as Session

    A->>R: GET /admin/barang/create
    R->>C: create()
    C->>KM: getAll()
    KM->>DB: SELECT * FROM kategori
    DB-->>KM: kategori list
    KM-->>C: kategori list
    alt Tidak ada kategori
        C->>S: setFlash('error', 'Tambah kategori dulu')
        C-->>A: Redirect /admin/kategori/create
    end
    C->>V: render('admin/barang/form')
    V-->>A: Form barang

    A->>R: POST /admin/barang/store
    R->>C: store()
    C->>C: Validasi (kode, barcode, nama, harga > 0, ...)
    C->>BM: isCodeUnique(kode_barang)
    BM->>DB: SELECT FROM barang WHERE kode_barang = ?
    DB-->>BM: result
    C->>BM: isBarcodeUnique(barcode)
    BM->>DB: SELECT FROM barang WHERE barcode = ?
    DB-->>BM: result

    alt Tidak valid / duplikat
        C->>S: setFlash('error', ...)
        C-->>A: Redirect kembali ke form
    end

    C->>BM: create(barang_data)
    BM->>DB: INSERT INTO barang (...)
    DB-->>BM: id
    BM-->>C: id
    C->>S: setFlash('success', 'Barang berhasil ditambahkan')
    C-->>A: Redirect /admin/barang
```

---

## 9. Batalkan Transaksi

```mermaid
sequenceDiagram
    actor A as Admin
    participant R as Router
    participant C as RiwayatController
    participant TM as Transaksi Model
    participant DM as DetailTransaksi Model
    participant BM as Barang Model
    participant DB as Database
    participant S as Session

    A->>R: POST /admin/riwayat-transaksi/cancel/{id}
    R->>C: cancel(id)
    C->>C: requireRole('admin')

    alt Alasan kosong
        C->>S: setFlash('error', 'Alasan wajib')
        C-->>A: Redirect
    end

    C->>TM: findById(id)
    TM->>DB: SELECT FROM transaksi
    DB-->>TM: transaksi
    TM-->>C: transaksi

    alt Tidak ada / sudah dibatalkan
        C->>S: setFlash('error', ...)
        C-->>A: Redirect
    end

    C->>DM: getByTransaksiId(id)
    DM->>DB: SELECT FROM detail_transaksi WHERE id_transaksi = ?
    DB-->>DM: items
    DM-->>C: items

    C->>DB: beginTransaction()

    loop Setiap item
        C->>BM: increaseStock(id_barang, qty)
        BM->>DB: UPDATE barang SET stok = stok + ?
        DB-->>BM: success
    end

    C->>TM: updateStatus(id, 'dibatalkan', alasan)
    TM->>DB: UPDATE transaksi SET status='dibatalkan', alasan=?
    DB-->>TM: success

    C->>DB: commit()
    C->>S: setFlash('success', 'Transaksi dibatalkan, stok dikembalikan')
    C-->>A: Redirect /admin/riwayat-transaksi
```

---

## 10. Edit Transaksi

```mermaid
sequenceDiagram
    actor A as Admin
    participant R as Router
    participant C as RiwayatController
    participant TM as Transaksi Model
    participant DM as DetailTransaksi Model
    participant BM as Barang Model
    participant RM as Restock Model
    participant DB as Database
    participant S as Session

    A->>R: POST /admin/riwayat-transaksi/update/{id}
    R->>C: update(id)
    C->>C: requireRole('admin')

    C->>TM: findById(id)
    TM-->>C: transaksi
    alt Tidak ada / dibatalkan
        C-->>A: Flash error + redirect
    end

    C->>C: parseEditCart(cart_json)
    alt Cart kosong
        C-->>A: Flash error + redirect ke form
    end

    C->>DM: getByTransaksiId(id)
    DM-->>C: oldItems

    C->>DB: beginTransaction()

    Note over C,DB: Step 1 - Kembalikan stok lama
    loop Setiap oldItem
        C->>BM: increaseStock(id_barang, qty)
        BM->>DB: UPDATE stok + qty
    end

    Note over C,DB: Step 2 - Validasi dan hitung stok baru
    loop Setiap newItem
        C->>BM: findActiveById(id_barang)
        BM-->>C: barang (stok sekarang)
        alt Stok kurang
            C->>DB: rollBack()
            C-->>A: Flash error
        end
        C->>RM: getLastHargaBeli(id_barang)
        RM-->>C: harga_beli
    end

    Note over C,DB: Step 3 - Kurangi stok baru
    loop Setiap newItem
        C->>BM: decreaseStock(id_barang, qty)
    end

    Note over C,DB: Step 4 - Hapus detail lama
    C->>DM: deleteByTransaksiId(id)
    DM->>DB: DELETE FROM detail_transaksi WHERE id_transaksi = ?

    Note over C,DB: Step 5 - Insert detail baru
    loop Setiap newItem
        C->>DM: create(detail_data)
        DM->>DB: INSERT INTO detail_transaksi
    end

    Note over C,DB: Step 6 - Update total
    C->>TM: updateTotals(id, totals)
    TM->>DB: UPDATE transaksi SET total_jual=?, ...
    C->>TM: updateMetodeBayar(id, metode)

    alt Cash dan nominal < total baru
        C->>DB: rollBack()
        C-->>A: Flash error
    end

    C->>DB: commit()
    C->>S: setFlash('success', 'Transaksi berhasil diubah')
    C-->>A: Redirect /admin/riwayat-transaksi
```

---

## 11. Lihat Laporan + Export Excel

```mermaid
sequenceDiagram
    actor A as Admin
    participant R as Router
    participant C as LaporanController
    participant M as Laporan Model
    participant DB as Database
    participant V as View

    A->>R: GET /admin/laporan/penjualan?tanggal_mulai=...
    R->>C: penjualan()
    C->>C: requireRole('admin')
    C->>C: Parse dan validasi filter tanggal
    alt tanggal_mulai > tanggal_selesai
        C->>C: Tukar posisi
    end

    C->>M: getPenjualanSummary(mulai, selesai)
    M->>DB: SELECT SUM(...) FROM transaksi WHERE tanggal BETWEEN ...
    DB-->>M: summary
    M-->>C: summary

    C->>M: getPenjualanHarian(mulai, selesai)
    M->>DB: SELECT GROUP BY DATE(tanggal)
    DB-->>M: data harian
    M-->>C: data harian

    C->>V: render('admin/laporan/penjualan', data)
    V-->>A: HTML laporan

    Note over A,V: Jika Admin klik Export
    A->>R: GET /admin/laporan/export-penjualan?...
    R->>C: exportPenjualan()
    C->>C: Set header Content-Type: application/vnd.ms-excel
    C->>C: Set header Content-Disposition: attachment
    C->>V: render tabel HTML sebagai .xls
    V-->>A: Download file .xls
```

---

## 12. Ganti Password Kasir

```mermaid
sequenceDiagram
    actor K as Kasir
    participant R as Router
    participant C as KasirController
    participant M as User Model
    participant DB as Database
    participant S as Session
    participant V as View

    K->>R: GET /kasir/profil
    R->>C: profil()
    C->>C: requireRole('kasir')
    C->>M: findById(userId)
    M->>DB: SELECT FROM users WHERE id = ?
    DB-->>M: userData
    M-->>C: userData
    alt User tidak ada
        C->>S: logout()
        C-->>K: Redirect /login
    end
    C->>V: render('kasir/profil', userData)
    V-->>K: Halaman profil + form ganti password

    K->>R: POST /kasir/update-password
    R->>C: updatePassword()
    C->>C: requireRole('kasir')
    C->>C: Validasi (current required, new min 8, konfirmasi sama)

    C->>M: findByIdWithPassword(userId)
    M->>DB: SELECT id, password FROM users WHERE id = ?
    DB-->>M: user + hashed password
    M-->>C: user

    C->>C: passwordVerify(current, hash)
    alt Password lama salah
        C->>S: set errors
        C-->>K: Redirect /kasir/profil
    end

    alt Validasi gagal
        C->>S: set errors
        C-->>K: Redirect /kasir/profil
    end

    C->>M: updateOwnPassword(userId, newPassword)
    M->>DB: UPDATE users SET password = ? WHERE id = ?
    DB-->>M: success
    M-->>C: true

    C->>S: setFlash('success', 'Password berhasil diperbarui')
    C-->>K: Redirect /kasir/profil
```

---

## Konvensi Komponen Sequence Diagram

| Komponen | Representasi | Deskripsi |
|---|---|---|
| Actor | `actor U as User` | User yang berinteraksi dengan sistem |
| Participant | `participant C as Controller` | Komponen sistem (Router, Controller, Model, dll) |
| Solid arrow (`->>`) | Synchronous message | Request / pemanggilan method |
| Dashed arrow (`-->>`) | Return message | Response / return value |
| `alt...else...end` | Alternative fragment | Percabangan kondisi |
| `loop...end` | Loop fragment | Iterasi |
| `Note over` | Note | Keterangan tahapan |

---

**Versi:** 1.0 | **Sinkron dengan:** branch `main`
**Next:** Class Diagram → ERD → DFD → SRS → User Story → Black Box → UAT
