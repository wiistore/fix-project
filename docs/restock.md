# Restock — Stok Masuk & Stok Keluar

---

## 1. Use Case Diagram

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart LR
    Admin((Admin))

    subgraph SISTEM["Sistem POS Kopsis — Restock"]
        UC_LIST[Lihat Riwayat Restock]
        UC_IN[Tambah Stok Masuk]
        UC_OUT[Kurangi Stok - Keluar]
        UC_HARGA[/Update Harga Jual Barang/]
        UC_CEK_STOK[/Validasi Stok Cukup/]
        UC_CEK_SUPPLIER[/Validasi Supplier Aktif/]
        UC_CEK_BARANG[/Validasi Barang Aktif/]
    end

    %% INCLUDE
    UC_IN -->|include| UC_CEK_BARANG
    UC_IN -->|include| UC_CEK_SUPPLIER
    UC_OUT -->|include| UC_CEK_BARANG
    UC_OUT -->|include| UC_CEK_STOK

    %% EXTEND
    UC_HARGA -.->|extend| UC_IN

    %% ACTOR
    Admin --> UC_LIST
    Admin --> UC_IN
    Admin --> UC_OUT
```

### Keterangan Relasi

| Tipe | Use Case Utama | Target | Penjelasan |
|------|---------------|--------|------------|
| include | Stok Masuk | Validasi Barang Aktif | Wajib cek barang aktif |
| include | Stok Masuk | Validasi Supplier Aktif | Wajib cek supplier aktif |
| include | Stok Keluar | Validasi Barang Aktif | Wajib cek barang aktif |
| include | Stok Keluar | Validasi Stok Cukup | Stok harus >= qty keluar |
| extend | Update Harga Jual | Stok Masuk | Opsional, hanya jika admin isi harga jual baru |

---

## 2. Sequence Diagram — Stok Masuk

```mermaid
sequenceDiagram
    title Sequence: Stok Masuk (Restock dari Supplier)

    actor Admin
    participant Browser
    participant Router
    participant RstCtrl as RestockController
    participant BrgModel as Barang Model
    participant SupModel as Supplier Model
    participant RstModel as Restock Model
    participant Session
    participant DB as Database

    Admin->>Browser: Buka /admin/restock/create?tipe=masuk
    Browser->>Router: GET /admin/restock/create
    Router->>RstCtrl: create()
    RstCtrl->>RstCtrl: requireRole('admin')
    RstCtrl->>BrgModel: getActive()
    BrgModel->>DB: SELECT barang aktif
    DB-->>BrgModel: list barang
    RstCtrl->>SupModel: getActive()
    SupModel->>DB: SELECT supplier aktif
    DB-->>SupModel: list supplier
    RstCtrl-->>Browser: Render form stok masuk

    Admin->>Browser: Pilih barang, supplier, isi qty & harga beli
    Admin->>Browser: Isi harga jual baru (opsional)
    Admin->>Browser: Klik Simpan

    Browser->>Router: POST /admin/restock/store
    Router->>RstCtrl: store()
    RstCtrl->>RstCtrl: requireRole('admin')
    RstCtrl->>RstCtrl: validatePayload(data)

    alt Validasi gagal
        RstCtrl->>Session: set('_errors', errors)
        RstCtrl-->>Browser: Redirect kembali ke form
    else Validasi berhasil
        RstCtrl->>BrgModel: findActiveById(id_barang)
        BrgModel->>DB: SELECT barang
        DB-->>BrgModel: data barang

        RstCtrl->>SupModel: findById(id_supplier)
        SupModel->>DB: SELECT supplier
        DB-->>SupModel: data supplier

        RstCtrl->>DB: beginTransaction()

        RstCtrl->>RstModel: create(data restock)
        RstModel->>DB: INSERT INTO restock (tipe='masuk')
        DB-->>RstModel: restock_id

        RstCtrl->>BrgModel: increaseStock(id, qty)
        BrgModel->>DB: UPDATE stok = stok + qty
        DB-->>BrgModel: OK

        opt Harga jual baru diisi
            RstCtrl->>BrgModel: updateHargaJual(id, harga_baru)
            BrgModel->>DB: UPDATE harga_jual
            DB-->>BrgModel: OK
        end

        RstCtrl->>DB: commit()
        RstCtrl->>Session: setFlash('success', 'Restock berhasil')
        RstCtrl-->>Browser: Redirect /admin/restock
    end
```

---

## 3. Sequence Diagram — Stok Keluar

```mermaid
sequenceDiagram
    title Sequence: Stok Keluar (Pengurangan/Penyesuaian)

    actor Admin
    participant Browser
    participant Router
    participant RstCtrl as RestockController
    participant BrgModel as Barang Model
    participant RstModel as Restock Model
    participant Session
    participant DB as Database

    Admin->>Browser: Buka /admin/restock/create?tipe=keluar
    Browser->>Router: GET /admin/restock/create
    Router->>RstCtrl: create()
    RstCtrl->>RstCtrl: requireRole('admin')
    RstCtrl->>BrgModel: getActive()
    RstCtrl-->>Browser: Render form stok keluar

    Admin->>Browser: Pilih barang, isi qty, harga beli, alasan
    Admin->>Browser: Klik Simpan

    Browser->>Router: POST /admin/restock/store
    Router->>RstCtrl: store()
    RstCtrl->>RstCtrl: validatePayload(data)

    alt Validasi gagal (alasan kosong / qty invalid)
        RstCtrl->>Session: set('_errors', errors)
        RstCtrl-->>Browser: Redirect kembali ke form
    else Validasi berhasil
        RstCtrl->>BrgModel: findActiveById(id_barang)
        BrgModel->>DB: SELECT barang + stok
        DB-->>BrgModel: data barang

        alt Stok < qty keluar
            RstCtrl->>Session: set('_errors', {qty: 'Stok tidak cukup'})
            RstCtrl-->>Browser: Redirect kembali
        else Stok cukup
            RstCtrl->>DB: beginTransaction()

            RstCtrl->>RstModel: create(data restock)
            RstModel->>DB: INSERT INTO restock (tipe='keluar')
            DB-->>RstModel: restock_id

            RstCtrl->>BrgModel: decreaseStock(id, qty)
            BrgModel->>DB: UPDATE stok = stok - qty WHERE stok >= qty
            DB-->>BrgModel: OK

            RstCtrl->>DB: commit()
            RstCtrl->>Session: setFlash('success', 'Stok berhasil dikurangi')
            RstCtrl-->>Browser: Redirect /admin/restock
        end
    end
```

---

## 4. Activity Diagram — Stok Masuk

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart TD
    A([Start]) --> B[Admin buka form Restock Stok Masuk]
    B --> C[Pilih barang]
    C --> D[Pilih supplier]
    D --> E[Input qty dan harga beli]
    E --> F[Input harga jual baru - opsional]
    F --> G[Input catatan - opsional]
    G --> H[Klik Simpan]

    H --> I{Validasi input OK?}
    I -->|Tidak| J[Tampilkan error validasi]
    J --> Z([Stop])

    I -->|Ya| K{Barang aktif?}
    K -->|Tidak| L[Error: Barang tidak ditemukan/nonaktif]
    L --> Z

    K -->|Ya| M{Supplier aktif?}
    M -->|Tidak| N[Error: Supplier nonaktif]
    N --> Z

    M -->|Ya| O[Begin Transaction]
    O --> P[Simpan record restock tipe=masuk]
    P --> Q[Tambah stok barang: stok + qty]
    Q --> R{Harga jual baru diisi?}

    R -->|Ya| S[Update harga jual barang]
    S --> T[Commit Transaction]
    R -->|Tidak| T

    T --> U[Tampilkan pesan sukses]
    U --> V[Redirect ke daftar restock]
    V --> Z
```

---

## 5. Activity Diagram — Stok Keluar

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart TD
    A([Start]) --> B[Admin buka form Restock Stok Keluar]
    B --> C[Pilih barang]
    C --> D[Input qty keluar]
    D --> E[Input harga beli - referensi]
    E --> F[Input alasan - WAJIB]
    F --> G[Input catatan - opsional]
    G --> H[Klik Simpan]

    H --> I{Validasi input OK?}
    I -->|Tidak| J[Tampilkan error validasi]
    J --> Z([Stop])

    I -->|Ya| K{Alasan terisi?}
    K -->|Tidak| L[Error: Alasan wajib diisi]
    L --> Z

    K -->|Ya| M{Barang aktif?}
    M -->|Tidak| N[Error: Barang tidak ditemukan/nonaktif]
    N --> Z

    M -->|Ya| O{Stok >= qty keluar?}
    O -->|Tidak| P[Error: Stok tidak cukup]
    P --> Z

    O -->|Ya| Q[Begin Transaction]
    Q --> R[Simpan record restock tipe=keluar]
    R --> S[Kurangi stok barang: stok - qty]
    S --> T[Commit Transaction]
    T --> U[Tampilkan pesan sukses]
    U --> V[Redirect ke daftar restock]
    V --> Z
```

---

## Catatan

- **Stok Masuk**: Supplier wajib dipilih, harga jual baru opsional
- **Stok Keluar**: Supplier opsional, alasan WAJIB diisi
- Semua operasi stok menggunakan **database transaction** untuk menjamin konsistensi
- Field `total_nilai` dihitung otomatis = `qty × harga_beli`
