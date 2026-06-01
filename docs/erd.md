# Entity Relationship Diagram (ERD) - Kopsis POS

## Mermaid ERD

```mermaid
erDiagram
    users {
        int id PK
        varchar username UK
        varchar email UK
        varchar password
        enum role "admin | kasir"
        tinyint is_protected
        enum status "aktif | nonaktif"
        timestamp created_at
        timestamp updated_at
    }

    kategori {
        int id PK
        varchar nama
        text deskripsi
        timestamp created_at
        timestamp updated_at
    }

    barang {
        int id PK
        varchar kode_barang UK
        varchar barcode UK
        varchar nama
        int id_kategori FK
        varchar satuan
        decimal harga_jual
        int stok
        int stok_minimum
        enum status "aktif | nonaktif"
        timestamp created_at
        timestamp updated_at
    }

    supplier {
        int id PK
        varchar nama
        varchar kontak_person
        varchar no_hp
        text alamat
        text keterangan
        enum status "aktif | nonaktif"
        timestamp created_at
        timestamp updated_at
    }

    restock {
        int id PK
        date tanggal
        enum tipe "masuk | keluar"
        int id_barang FK
        int id_supplier FK "nullable"
        int id_user FK
        int qty
        decimal harga_beli
        decimal harga_jual_baru "nullable"
        decimal total_nilai
        text catatan
        text alasan "nullable, wajib jika tipe=keluar"
        timestamp created_at
    }

    transaksi {
        int id PK
        varchar kode_transaksi UK
        int id_user FK
        datetime tanggal
        decimal total_jual
        decimal total_beli
        decimal total_laba
        enum metode_bayar "cash | transfer | qris | ewallet"
        decimal nominal_bayar
        decimal kembalian
        enum status "selesai | diubah | dibatalkan"
        text alasan_batal "nullable"
        datetime edited_at "nullable"
        timestamp created_at
    }

    detail_transaksi {
        int id PK
        int id_transaksi FK
        int id_barang FK
        int qty
        decimal harga_jual
        decimal harga_beli
        decimal subtotal_jual
        decimal subtotal_beli
        decimal laba_item
    }

    %% === RELATIONSHIPS ===
    users ||--o{ transaksi : "membuat"
    users ||--o{ restock : "melakukan"
    kategori ||--o{ barang : "memiliki"
    barang ||--o{ detail_transaksi : "dijual di"
    barang ||--o{ restock : "di-restock"
    supplier ||--o{ restock : "memasok"
    transaksi ||--|{ detail_transaksi : "terdiri dari"
```

## Penjelasan Relasi

| Relasi | Keterangan |
|--------|------------|
| `users` → `transaksi` | Satu user (admin/kasir) bisa membuat banyak transaksi |
| `users` → `restock` | Admin yang melakukan restock tercatat sebagai pelaku |
| `kategori` → `barang` | Satu kategori memiliki banyak barang |
| `barang` → `detail_transaksi` | Satu barang bisa muncul di banyak detail transaksi |
| `barang` → `restock` | Satu barang bisa di-restock berkali-kali |
| `supplier` → `restock` | Satu supplier bisa memasok banyak kali (nullable untuk tipe keluar) |
| `transaksi` → `detail_transaksi` | Satu transaksi terdiri dari satu atau lebih item detail |

## Constraint Penting

- `barang.id_kategori` → RESTRICT ON DELETE (kategori tidak bisa dihapus kalau masih punya barang)
- `detail_transaksi.id_transaksi` → CASCADE ON DELETE (hapus transaksi = hapus detailnya)
- `restock.id_supplier` → RESTRICT ON DELETE (supplier tidak bisa dihapus kalau ada histori restock)
- `transaksi.id_user` → RESTRICT ON DELETE (user tidak bisa dihapus kalau punya transaksi)
