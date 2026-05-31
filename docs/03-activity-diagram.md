# Activity Diagram - Sistem POS

> Activity Diagram untuk aplikasi kasir berbasis **PHP Native (custom MVC)**.
> Disusun berdasarkan logika kode aktual pada `app/controllers/*` (sinkron dengan branch `main`).
> Format: **Mermaid** (render di GitHub / VS Code / [mermaid.live](https://mermaid.live)).
>
> Activity Diagram fokus pada **alur aktivitas** antara aktor dan sistem, lengkap dengan
> percabangan (decision), aksi paralel, dan pemisahan tanggung jawab (swimlane via subgraph).

---

## Daftar Isi

1. [Login](#1-login)
2. [Transaksi Penjualan (POS)](#2-transaksi-penjualan-pos)
3. [Restock Stok (Masuk & Keluar)](#3-restock-stok-masuk--keluar)
4. [Tambah / Edit Barang](#4-tambah--edit-barang)
5. [Batalkan Transaksi (Refund)](#5-batalkan-transaksi-refund)
6. [Edit Transaksi](#6-edit-transaksi)
7. [Cetak Struk PDF](#7-cetak-struk-pdf)
8. [Lihat & Export Laporan](#8-lihat--export-laporan)
9. [Ganti Password Kasir](#9-ganti-password-kasir)

> Catatan notasi: Mermaid tidak punya bentuk activity-diagram khusus, jadi swimlane
> direpresentasikan dengan `subgraph` (kolom Aktor vs Sistem vs Database).
> Titik mulai `([Start])`, titik akhir `([End])`, decision `{ }`, fork/join dijelaskan via teks.

---

## 1. Login

```mermaid
flowchart TD
    subgraph U["Aktor (User)"]
        U1([Mulai]) --> U2[Buka halaman /login]
        U2 --> U3[Isi username & password]
        U3 --> U4[Klik tombol Login]
    end

    subgraph S["Sistem"]
        S1{Input kosong?}
        S2[Cari user by username]
        S3{User ada &<br/>password cocok?}
        S4{Status aktif?}
        S5{Role admin/kasir?}
        S6[Set session, hapus password]
        S7[Redirect ke dashboard sesuai role]
        SE[Tampilkan flash error]
    end

    U4 --> S1
    S1 -- Ya --> SE
    S1 -- Tidak --> S2
    S2 --> S3
    S3 -- Tidak --> SE
    S3 -- Ya --> S4
    S4 -- Tidak --> SE
    S4 -- Ya --> S5
    S5 -- Tidak --> SE
    S5 -- Ya --> S6
    S6 --> S7
    SE --> U2
    S7 --> E([Selesai])
```

---

## 2. Transaksi Penjualan (POS)

> Aktivitas inti. Melibatkan Aktor (Admin/Kasir), Sistem, dan Database dengan DB transaction.

```mermaid
flowchart TD
    subgraph A["Aktor (Admin / Kasir)"]
        A1([Mulai]) --> A2[Buka halaman POS]
        A2 --> A3[Pilih barang & atur qty]
        A3 --> A4[Pilih metode bayar]
        A4 --> A5[Isi nominal bayar - jika cash]
        A5 --> A6[Submit transaksi]
    end

    subgraph S["Sistem"]
        S1[Normalisasi keranjang]
        S2{Valid? cart, metode,<br/>nominal cash}
        S3[BEGIN TRANSACTION]
        S4[Hitung ulang harga dari DB]
        S5{Barang aktif & stok cukup?}
        S6{Cash: nominal >= total?}
        S7[Generate kode transaksi]
        S8[Simpan transaksi + detail]
        S9[Kurangi stok tiap item]
        S10[COMMIT]
        S11[Redirect ke struk]
        SR[ROLLBACK + flash error]
        SE[Flash error validasi]
    end

    subgraph D["Database"]
        D1[(Tabel barang)]
        D2[(Tabel transaksi)]
        D3[(Tabel detail_transaksi)]
    end

    A6 --> S1 --> S2
    S2 -- Tidak --> SE --> A2
    S2 -- Ya --> S3 --> S4
    S4 -.baca.-> D1
    S4 --> S5
    S5 -- Tidak --> SR
    S5 -- Ya --> S6
    S6 -- Tidak --> SR
    S6 -- Ya --> S7 --> S8
    S8 -.tulis.-> D2
    S8 -.tulis.-> D3
    S8 --> S9
    S9 -.update.-> D1
    S9 --> S10 --> S11 --> E([Selesai])
    SR --> A2
```

---

## 3. Restock Stok (Masuk & Keluar)

```mermaid
flowchart TD
    subgraph A["Admin"]
        A1([Mulai]) --> A2[Pilih tipe: masuk / keluar]
        A2 --> A3[Isi form restock]
        A3 --> A4[Submit]
    end

    subgraph S["Sistem"]
        S1[Validasi payload]
        S2{tipe masuk?}
        S3[Cek supplier aktif]
        S4[Cek qty <= stok + alasan wajib]
        S5{Valid?}
        S6[BEGIN TRANSACTION]
        S7[Insert data restock]
        S8{tipe masuk?}
        S9[increaseStock + update harga jual opsional]
        S10[decreaseStock]
        S11[COMMIT + flash success]
        SR[ROLLBACK + flash error]
        SE[Set errors, kembali ke form]
    end

    subgraph D["Database"]
        D1[(Tabel restock)]
        D2[(Tabel barang)]
    end

    A4 --> S1 --> S2
    S2 -- Ya --> S3 --> S5
    S2 -- Tidak --> S4 --> S5
    S5 -- Tidak --> SE --> A3
    S5 -- Ya --> S6 --> S7
    S7 -.tulis.-> D1
    S7 --> S8
    S8 -- Ya --> S9
    S8 -- Tidak --> S10
    S9 -.update.-> D2
    S10 -.update.-> D2
    S9 --> S11
    S10 --> S11
    S11 --> E([Selesai])
    SR --> A3
```

---

## 4. Tambah / Edit Barang

```mermaid
flowchart TD
    subgraph A["Admin"]
        A1([Mulai]) --> A2[Buka form barang]
        A2 --> A3[Isi data: kode, barcode, nama,<br/>kategori, harga, stok minimum]
        A3 --> A4[Submit]
    end

    subgraph S["Sistem"]
        S0{Ada kategori?}
        S1[Validasi field]
        S2{harga_jual > 0 &<br/>kode/barcode unik?}
        S3[Insert / Update barang]
        S4[Flash success, redirect index]
        SE[Flash error, kembali ke form]
        SK[Redirect ke buat kategori]
    end

    subgraph D["Database"]
        D1[(Tabel barang)]
        D2[(Tabel kategori)]
    end

    A2 --> S0
    S0 -.cek.-> D2
    S0 -- Tidak --> SK
    S0 -- Ya --> A3
    A4 --> S1 --> S2
    S2 -- Tidak --> SE --> A3
    S2 -- Ya --> S3
    S3 -.tulis.-> D1
    S3 --> S4 --> E([Selesai])
```

---

## 5. Batalkan Transaksi (Refund)

```mermaid
flowchart TD
    subgraph A["Admin"]
        A1([Mulai]) --> A2[Buka riwayat transaksi]
        A2 --> A3[Klik Batalkan + isi alasan]
        A3 --> A4[Konfirmasi]
    end

    subgraph S["Sistem"]
        S1{Alasan diisi?}
        S2{Transaksi ada &<br/>belum dibatalkan?}
        S3[BEGIN TRANSACTION]
        S4[Kembalikan stok semua item]
        S5[Update status = dibatalkan + alasan]
        S6[COMMIT + flash success]
        SR[ROLLBACK + flash error]
        SE[Flash error]
    end

    subgraph D["Database"]
        D1[(Tabel barang)]
        D2[(Tabel transaksi)]
    end

    A4 --> S1
    S1 -- Tidak --> SE --> A2
    S1 -- Ya --> S2
    S2 -- Tidak --> SE
    S2 -- Ya --> S3 --> S4
    S4 -.update.-> D1
    S4 --> S5
    S5 -.update.-> D2
    S5 --> S6 --> E([Selesai])
    SR --> A2
```

---

## 6. Edit Transaksi

> Proses 6 langkah dalam satu DB transaction: kembalikan stok lama → validasi → kurangi stok baru → ganti detail → update total.

```mermaid
flowchart TD
    subgraph A["Admin"]
        A1([Mulai]) --> A2[Buka form edit transaksi]
        A2 --> A3[Ubah item / qty]
        A3 --> A4[Submit]
    end

    subgraph S["Sistem"]
        S1{Status != dibatalkan?}
        S2{Cart baru tidak kosong?}
        S3[BEGIN TRANSACTION]
        S4[Step1: kembalikan stok lama]
        S5[Step2: hitung ulang + validasi stok baru]
        S6{Stok cukup?}
        S7[Step3: kurangi stok baru]
        S8[Step4: hapus detail lama]
        S9[Step5: insert detail baru]
        S10[Step6: update total + metode]
        S11{Cash & nominal cukup?}
        S12[COMMIT + flash success]
        SR[ROLLBACK + flash error]
        SE[Flash error]
    end

    A4 --> S1
    S1 -- Tidak --> SE --> A2
    S1 -- Ya --> S2
    S2 -- Tidak --> SE
    S2 -- Ya --> S3 --> S4 --> S5 --> S6
    S6 -- Tidak --> SR --> A2
    S6 -- Ya --> S7 --> S8 --> S9 --> S10 --> S11
    S11 -- Tidak --> SR
    S11 -- Ya --> S12 --> E([Selesai])
```

---

## 7. Cetak Struk PDF

```mermaid
flowchart TD
    subgraph A["Aktor (Admin / Kasir)"]
        A1([Mulai]) --> A2[Buka struk transaksi]
        A2 --> A3[Klik Download PDF]
    end

    subgraph S["Sistem"]
        S1{Transaksi ada?}
        S2{Kasir & bukan miliknya?}
        S3{Dompdf terinstall?}
        S4[Render HTML struk-pdf]
        S5[Generate PDF paper 80mm]
        S6[Stream sebagai attachment]
        SE[Flash error / 403]
        SX[RuntimeException]
    end

    A3 --> S1
    S1 -- Tidak --> SE --> A2
    S1 -- Ya --> S2
    S2 -- Ya --> SE
    S2 -- Tidak --> S3
    S3 -- Tidak --> SX
    S3 -- Ya --> S4 --> S5 --> S6 --> E([Selesai: file terunduh])
```

---

## 8. Lihat & Export Laporan

```mermaid
flowchart TD
    subgraph A["Admin"]
        A1([Mulai]) --> A2[Pilih jenis laporan]
        A2 --> A3[Atur filter tanggal]
        A3 --> A4[Lihat laporan]
        A4 --> A5{Export?}
    end

    subgraph S["Sistem"]
        S1{tanggal_mulai > selesai?}
        S2[Tukar posisi tanggal]
        S3[Ambil data sesuai jenis & filter]
        S4[Render view laporan]
        S5[Set header Excel + attachment]
        S6[Render tabel HTML .xls]
    end

    A3 --> S1
    S1 -- Ya --> S2 --> S3
    S1 -- Tidak --> S3
    S3 --> S4 --> A4
    A5 -- Tidak --> E([Selesai])
    A5 -- Ya --> S5 --> S6 --> E2([Selesai: file .xls terunduh])
```

---

## 9. Ganti Password Kasir

```mermaid
flowchart TD
    subgraph A["Kasir"]
        A1([Mulai]) --> A2[Buka halaman profil]
        A2 --> A3[Isi password lama, baru, konfirmasi]
        A3 --> A4[Submit]
    end

    subgraph S["Sistem"]
        S1{Akun masih ada di DB?}
        S2[Validasi: baru min 8, konfirmasi sama]
        S3{Password lama cocok?}
        S4[Hash + simpan password baru]
        S5[Flash success]
        SE[Flash error]
        SL[Logout otomatis]
    end

    A2 --> S1
    S1 -- Tidak --> SL --> E([Selesai])
    S1 -- Ya --> A3
    A4 --> S2 --> S3
    S3 -- Tidak --> SE --> A2
    S3 -- Ya --> S4 --> S5 --> E
```

---

## Konvensi Activity Diagram

| Elemen | Representasi Mermaid | Arti |
|---|---|---|
| Initial node | `([Mulai])` | Titik awal aktivitas |
| Final node | `([Selesai])` | Titik akhir aktivitas |
| Action | `[Teks]` | Aksi / aktivitas |
| Decision | `{Teks}` | Percabangan kondisi |
| Swimlane | `subgraph` | Pemisahan tanggung jawab (Aktor / Sistem / Database) |
| Object flow | `-.label.->` | Interaksi baca/tulis ke database |

> Untuk diagram baku UML (fork/join bar, swimlane vertikal asli), gunakan tools seperti
> draw.io / StarUML / Visual Paradigm dan adaptasi dari struktur di atas.

---

**Versi:** 1.0 | **Sinkron dengan:** branch `main`
**Next:** Sequence Diagram → Class Diagram → ERD → DFD → SRS → User Story → Black Box → UAT
