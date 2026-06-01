# Entity Relationship Diagram (ERD) — Kopsis POS

---

## Diagram

```mermaid
erDiagram
    users {
        int id PK
        varchar username UK
        varchar email UK
        varchar password
        enum role "admin | kasir"
        tinyint is_protected "0 | 1"
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
        varchar barcode UK "nullable"
        varchar nama
        int id_kategori FK
        varchar satuan "default: pcs"
        decimal harga_jual
        int stok
        int stok_minimum "default: 5"
        enum status "aktif | nonaktif"
        timestamp created_at
        timestamp updated_at
    }

    supplier {
        int id PK
        varchar nama
        varchar kontak_person "nullable"
        varchar no_hp "nullable"
        text alamat "nullable"
        text keterangan "nullable"
        enum status "aktif | nonaktif"
        timestamp created_at
        timestamp updated_at
    }

    restock {
        int id PK
        date tanggal
        enum tipe "masuk | keluar"
        int id_barang FK
        int id_supplier FK "nullable jika tipe=keluar"
        int id_user FK
        int qty
        decimal harga_beli
        decimal harga_jual_baru "nullable"
        decimal total_nilai "qty x harga_beli"
        text catatan "nullable"
        text alasan "nullable - wajib jika tipe=keluar"
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
        decimal subtotal_jual "qty x harga_jual"
        decimal subtotal_beli "qty x harga_beli"
        decimal laba_item "subtotal_jual - subtotal_beli"
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

---

## Penjelasan Relasi

| No | Relasi | Kardinalitas | Keterangan |
|----|--------|-------------|------------|
| 1 | `users` → `transaksi` | 1:N | Satu user (admin/kasir) bisa membuat banyak transaksi |
| 2 | `users` → `restock` | 1:N | Admin yang melakukan restock tercatat sebagai pelaku |
| 3 | `kategori` → `barang` | 1:N | Satu kategori memiliki banyak barang |
| 4 | `barang` → `detail_transaksi` | 1:N | Satu barang bisa muncul di banyak detail transaksi |
| 5 | `barang` → `restock` | 1:N | Satu barang bisa di-restock berkali-kali |
| 6 | `supplier` → `restock` | 1:N | Satu supplier bisa memasok banyak kali (nullable untuk tipe keluar) |
| 7 | `transaksi` → `detail_transaksi` | 1:N | Satu transaksi terdiri dari satu atau lebih item detail |

---

## Foreign Key Constraints

| Tabel | FK Column | References | ON DELETE | ON UPDATE |
|-------|-----------|-----------|-----------|-----------|
| `barang` | `id_kategori` | `kategori(id)` | RESTRICT | CASCADE |
| `detail_transaksi` | `id_transaksi` | `transaksi(id)` | CASCADE | CASCADE |
| `detail_transaksi` | `id_barang` | `barang(id)` | RESTRICT | CASCADE |
| `restock` | `id_barang` | `barang(id)` | RESTRICT | CASCADE |
| `restock` | `id_supplier` | `supplier(id)` | RESTRICT | CASCADE |
| `restock` | `id_user` | `users(id)` | RESTRICT | CASCADE |
| `transaksi` | `id_user` | `users(id)` | RESTRICT | CASCADE |

---

## Catatan Constraint

- **Kategori** tidak bisa dihapus jika masih punya barang (RESTRICT)
- **Barang** tidak bisa dihapus jika ada di detail_transaksi atau restock (RESTRICT)
- **Supplier** tidak bisa dihapus jika ada histori restock (RESTRICT)
- **User** tidak bisa dihapus jika punya transaksi (RESTRICT)
- **Transaksi** dihapus → semua detail_transaksi ikut terhapus (CASCADE)
- **Admin utama** (is_protected=1) tidak bisa diedit/dihapus dari menu user
