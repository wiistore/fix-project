# DFD Level 1 - Notasi Yourdon/DeMarco

> Pecahan dari proses tunggal "Sistem POS Koperasi" di DFD Level 0 menjadi sub-proses utama.

```plantuml
@startuml
title DFD Level 1 - Sistem POS Koperasi

skinparam linetype ortho
skinparam shadowing false
skinparam nodesep 25
skinparam ranksep 40
skinparam padding 2

skinparam rectangle {
    BackgroundColor #E0FFFF
    BorderColor #333
    FontSize 12
}
skinparam usecase {
    BackgroundColor #FFF8DC
    BorderColor #333
    FontSize 11
}
skinparam database {
    BackgroundColor #F5F5DC
    BorderColor #333
    FontSize 11
}

' ============================
' EXTERNAL ENTITIES (kotak)
' ============================
rectangle "Admin" as Admin
rectangle "Kasir" as Kasir

' ============================
' PROSES (lingkaran/oval)
' ============================
usecase "1.0\nLogin" as P1
usecase "2.0\nKelola\nData Master" as P2
usecase "3.0\nPenyesuaian\nStok" as P3
usecase "4.0\nTransaksi\nPenjualan" as P4
usecase "5.0\nRiwayat\nTransaksi" as P5
usecase "6.0\nLaporan" as P6
usecase "7.0\nProfile\nKasir" as P7

' ============================
' DATA STORES (silinder/database)
' ============================
database "D1: users" as DS1
database "D2: kategori" as DS2
database "D3: barang" as DS3
database "D4: supplier" as DS4
database "D5: restock" as DS5
database "D6: transaksi" as DS6
database "D7: detail_transaksi" as DS7

' ============================
' ARUS DATA - LOGIN (1.0)
' ============================
Admin --> P1 : kredensial
Kasir --> P1 : kredensial
P1 --> DS1 : verifikasi
DS1 --> P1 : data user
P1 --> Admin : sesi admin
P1 --> Kasir : sesi kasir

' ============================
' ARUS DATA - DATA MASTER (2.0)
' ============================
Admin --> P2 : data master\n(user/kategori/\nbarang/supplier)
P2 --> DS1 : simpan/update user
P2 --> DS2 : simpan/update kategori
P2 --> DS3 : simpan/update barang
P2 --> DS4 : simpan/update supplier
DS1 --> P2 : daftar user
DS2 --> P2 : daftar kategori
DS3 --> P2 : daftar barang
DS4 --> P2 : daftar supplier
P2 --> Admin : tampilan data

' ============================
' ARUS DATA - PENYESUAIAN STOK (3.0)
' ============================
Admin --> P3 : data restock\n(qty, tipe, alasan)
DS3 --> P3 : data barang
DS4 --> P3 : data supplier
P3 --> DS5 : simpan riwayat
P3 --> DS3 : update stok
P3 --> Admin : konfirmasi

' ============================
' ARUS DATA - TRANSAKSI (4.0)
' ============================
Kasir --> P4 : item & qty\nmetode bayar
DS3 --> P4 : data barang
P4 --> DS6 : simpan transaksi
P4 --> DS7 : simpan detail
P4 --> DS3 : update stok
P4 --> Kasir : struk / nota

' ============================
' ARUS DATA - RIWAYAT TRANSAKSI (5.0)
' ============================
Admin --> P5 : aksi (lihat/edit/batal)
DS6 --> P5 : data transaksi
DS7 --> P5 : detail transaksi
P5 --> DS6 : update/batal
P5 --> DS7 : update detail
P5 --> DS3 : rollback stok
P5 --> Admin : detail / konfirmasi

' ============================
' ARUS DATA - LAPORAN (6.0)
' ============================
Admin --> P6 : filter periode\njenis laporan
DS6 --> P6 : data transaksi
DS7 --> P6 : detail transaksi
DS5 --> P6 : data restock
DS3 --> P6 : data barang
P6 --> Admin : laporan / file Excel

' ============================
' ARUS DATA - PROFILE KASIR (7.0)
' ============================
Kasir --> P7 : password baru
DS1 --> P7 : data profil
P7 --> DS1 : update password
P7 --> Kasir : konfirmasi

@enduml
```

## Daftar Sub-Proses

| Kode | Nama Proses | Aktor |
|---|---|---|
| 1.0 | Login | Admin, Kasir |
| 2.0 | Kelola Data Master (User, Kategori, Barang, Supplier) | Admin |
| 3.0 | Penyesuaian Stok (Restock In/Out) | Admin |
| 4.0 | Transaksi Penjualan | Kasir |
| 5.0 | Riwayat Transaksi (Lihat/Edit/Batal) | Admin |
| 6.0 | Laporan (Penjualan, Laba, Terlaris, Restock) | Admin |
| 7.0 | Profile Kasir (Reset Password) | Kasir |

## Daftar Data Store

| Kode | Tabel | Keterangan |
|---|---|---|
| D1 | users | Data akun pengguna |
| D2 | kategori | Kategori barang |
| D3 | barang | Master barang & stok |
| D4 | supplier | Data supplier |
| D5 | restock | Riwayat tambah/kurang stok |
| D6 | transaksi | Header transaksi |
| D7 | detail_transaksi | Item per transaksi |
