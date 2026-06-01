# ERD - Notasi Chen

> Notasi Chen klasik: Entitas (kotak), Atribut (oval), Relasi (rhombus / pakai class stereotype).
> PlantUML tidak punya native diamond shape, jadi relasi dirender sebagai class dengan stereotype `<<R>>`.

```plantuml
@startuml
title ERD - Sistem POS Koperasi (Notasi Chen)

skinparam linetype ortho
skinparam shadowing false
skinparam nodesep 18
skinparam ranksep 25
skinparam padding 2

skinparam rectangle {
    BackgroundColor #FFF8DC
    BorderColor #333
    FontSize 12
}
skinparam usecase {
    BackgroundColor #E0FFFF
    BorderColor #333
    FontSize 10
}
skinparam class {
    BackgroundColor<<R>> #FFD1A4
    BorderColor<<R>> #B8860B
    FontSize 11
}
hide <<R>> circle
hide <<R>> members

' ============================
' ENTITAS (Kotak)
' ============================
rectangle "User" as E_User
rectangle "Kategori" as E_Kategori
rectangle "Barang" as E_Barang
rectangle "Supplier" as E_Supplier
rectangle "Restock" as E_Restock
rectangle "Transaksi" as E_Transaksi
rectangle "Detail_Transaksi" as E_Detail

' ============================
' ATRIBUT - User
' ============================
usecase "id" as u1
usecase "username" as u2
usecase "email" as u3
usecase "password" as u4
usecase "role" as u5
usecase "status" as u6

E_User -- u1
E_User -- u2
E_User -- u3
E_User -- u4
E_User -- u5
E_User -- u6

' ============================
' ATRIBUT - Kategori
' ============================
usecase "id" as k1
usecase "nama" as k2
usecase "deskripsi" as k3

E_Kategori -- k1
E_Kategori -- k2
E_Kategori -- k3

' ============================
' ATRIBUT - Barang
' ============================
usecase "id" as b1
usecase "kode_barang" as b2
usecase "nama" as b3
usecase "barcode" as b4
usecase "harga_jual" as b5
usecase "stok" as b6
usecase "satuan" as b7
usecase "status" as b8

E_Barang -- b1
E_Barang -- b2
E_Barang -- b3
E_Barang -- b4
E_Barang -- b5
E_Barang -- b6
E_Barang -- b7
E_Barang -- b8

' ============================
' ATRIBUT - Supplier
' ============================
usecase "id" as s1
usecase "nama" as s2
usecase "kontak" as s3
usecase "no_hp" as s4
usecase "alamat" as s5
usecase "status" as s6

E_Supplier -- s1
E_Supplier -- s2
E_Supplier -- s3
E_Supplier -- s4
E_Supplier -- s5
E_Supplier -- s6

' ============================
' ATRIBUT - Restock
' ============================
usecase "id" as r1
usecase "tanggal" as r2
usecase "tipe" as r3
usecase "qty" as r4
usecase "harga_beli" as r5
usecase "total_nilai" as r6
usecase "alasan" as r7

E_Restock -- r1
E_Restock -- r2
E_Restock -- r3
E_Restock -- r4
E_Restock -- r5
E_Restock -- r6
E_Restock -- r7

' ============================
' ATRIBUT - Transaksi
' ============================
usecase "id" as t1
usecase "kode_transaksi" as t2
usecase "tanggal" as t3
usecase "total_jual" as t4
usecase "total_laba" as t5
usecase "metode_bayar" as t6
usecase "nominal_bayar" as t7
usecase "kembalian" as t8
usecase "status" as t9

E_Transaksi -- t1
E_Transaksi -- t2
E_Transaksi -- t3
E_Transaksi -- t4
E_Transaksi -- t5
E_Transaksi -- t6
E_Transaksi -- t7
E_Transaksi -- t8
E_Transaksi -- t9

' ============================
' ATRIBUT - Detail_Transaksi
' ============================
usecase "id" as d1
usecase "qty" as d2
usecase "harga_jual" as d3
usecase "subtotal_jual" as d4
usecase "laba_item" as d5

E_Detail -- d1
E_Detail -- d2
E_Detail -- d3
E_Detail -- d4
E_Detail -- d5

' ============================
' RELASI (Diamond - workaround pakai class)
' ============================
class "Memiliki" as Rel_Mem <<R>>
class "Mengelola" as Rel_Kel <<R>>
class "Membuat" as Rel_Buat <<R>>
class "Memuat" as Rel_Muat <<R>>
class "Mereferensi" as Rel_Ref <<R>>
class "Disuplai" as Rel_Sup <<R>>
class "DiInputOleh" as Rel_Inp <<R>>

' Kategori 1 -- M Barang
E_Kategori "1" -- Rel_Mem
Rel_Mem -- "M" E_Barang

' User 1 -- M Transaksi
E_User "1" -- Rel_Buat
Rel_Buat -- "M" E_Transaksi

' Transaksi 1 -- M Detail_Transaksi
E_Transaksi "1" -- Rel_Muat
Rel_Muat -- "M" E_Detail

' Detail_Transaksi M -- 1 Barang
E_Detail "M" -- Rel_Ref
Rel_Ref -- "1" E_Barang

' Restock M -- 1 Barang
E_Restock "M" -- Rel_Kel
Rel_Kel -- "1" E_Barang

' Restock M -- 1 Supplier (optional)
E_Restock "M" -- Rel_Sup
Rel_Sup -- "0..1" E_Supplier

' Restock M -- 1 User
E_Restock "M" -- Rel_Inp
Rel_Inp -- "1" E_User

@enduml
```

## Catatan Notasi

| Bentuk | Arti |
|---|---|
| Kotak (Rectangle) | Entitas |
| Oval (Ellipse) | Atribut |
| `<<R>>` Class | Relasi (workaround diamond di PlantUML) |
| Label `1`, `M`, `0..1` | Kardinalitas |

## Daftar Relasi

| # | Entitas A | Relasi | Entitas B | Kardinalitas |
|---|---|---|---|---|
| 1 | Kategori | Memiliki | Barang | 1 : M |
| 2 | User | Membuat | Transaksi | 1 : M |
| 3 | Transaksi | Memuat | Detail_Transaksi | 1 : M |
| 4 | Detail_Transaksi | Mereferensi | Barang | M : 1 |
| 5 | Restock | Mengelola | Barang | M : 1 |
| 6 | Restock | Disuplai | Supplier | M : 0..1 |
| 7 | Restock | DiInputOleh | User | M : 1 |
