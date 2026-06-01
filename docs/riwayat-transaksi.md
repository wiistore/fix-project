# Riwayat Transaksi — Refund & Edit Transaksi

---

## 1. Use Case Diagram

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart LR
    Admin((Admin))

    subgraph SISTEM["Sistem POS Kopsis — Riwayat Transaksi"]
        UC_LIST[Lihat Riwayat Transaksi]
        UC_DETAIL[Lihat Detail Transaksi]
        UC_EDIT[/Edit Transaksi/]
        UC_CANCEL[/Batalkan Transaksi - Refund/]
        UC_RETURN[/Kembalikan Stok/]
        UC_FILTER[/Filter Periode Tanggal/]
        UC_VALIDASI_EDIT[/Validasi Keranjang Baru/]
        UC_RECALC[/Hitung Ulang Total dan Laba/]
    end

    %% INCLUDE
    UC_CANCEL -->|include| UC_RETURN
    UC_EDIT -->|include| UC_VALIDASI_EDIT
    UC_EDIT -->|include| UC_RETURN
    UC_EDIT -->|include| UC_RECALC

    %% EXTEND
    UC_EDIT -.->|extend| UC_LIST
    UC_CANCEL -.->|extend| UC_LIST
    UC_DETAIL -.->|extend| UC_LIST
    UC_FILTER -.->|extend| UC_LIST

    %% ACTOR
    Admin --> UC_LIST
```

### Keterangan Relasi

| Tipe | Use Case Utama | Target | Penjelasan |
|------|---------------|--------|------------|
| include | Batalkan Transaksi | Kembalikan Stok | Stok otomatis dikembalikan saat refund |
| include | Edit Transaksi | Kembalikan Stok | Stok lama dikembalikan dulu |
| include | Edit Transaksi | Validasi Keranjang Baru | Keranjang edit harus valid |
| include | Edit Transaksi | Hitung Ulang Total dan Laba | Recalculate dari DB |
| extend | Edit Transaksi | Riwayat | Opsional, hanya jika status = selesai |
| extend | Batalkan Transaksi | Riwayat | Opsional, hanya jika status = selesai |
| extend | Detail | Riwayat | Opsional, klik untuk detail |
| extend | Filter Periode | Riwayat | Opsional filter tanggal |

---

## 2. Sequence Diagram — Batalkan Transaksi (Refund)

```mermaid
sequenceDiagram
    title Sequence: Batalkan Transaksi (Refund)

    actor Admin
    participant Browser
    participant Router
    participant RiwCtrl as RiwayatController
    participant TrxModel as Transaksi Model
    participant DetModel as DetailTransaksi Model
    participant BrgModel as Barang Model
    participant Session
    participant DB as Database

    Admin->>Browser: Klik Batalkan pada transaksi tertentu
    Admin->>Browser: Input alasan pembatalan
    Admin->>Browser: Klik Konfirmasi Batalkan

    Browser->>Router: POST /admin/riwayat-transaksi/cancel/{id}
    Router->>RiwCtrl: cancel(id)
    RiwCtrl->>RiwCtrl: requireRole('admin')

    RiwCtrl->>RiwCtrl: Validasi alasan tidak kosong
    alt Alasan kosong
        RiwCtrl->>Session: setFlash('error', 'Alasan wajib diisi')
        RiwCtrl-->>Browser: Redirect /admin/riwayat-transaksi
    else Alasan terisi
        RiwCtrl->>TrxModel: findById(id)
        TrxModel->>DB: SELECT transaksi
        DB-->>TrxModel: data transaksi

        alt Transaksi tidak ditemukan
            RiwCtrl-->>Browser: Error tidak ditemukan
        else Transaksi ada
            alt Status sudah dibatalkan
                RiwCtrl->>Session: setFlash('error', 'Sudah dibatalkan sebelumnya')
                RiwCtrl-->>Browser: Redirect
            else Status = selesai/diubah
                RiwCtrl->>DetModel: getByTransaksiId(id)
                DetModel->>DB: SELECT detail items
                DB-->>DetModel: list items

                RiwCtrl->>DB: beginTransaction()

                loop Setiap item
                    RiwCtrl->>BrgModel: increaseStock(id_barang, qty)
                    BrgModel->>DB: UPDATE stok = stok + qty
                end

                RiwCtrl->>TrxModel: updateStatus(id, 'dibatalkan', alasan)
                TrxModel->>DB: UPDATE status & alasan_batal
                DB-->>TrxModel: OK

                RiwCtrl->>DB: commit()
                RiwCtrl->>Session: setFlash('success', 'Transaksi dibatalkan, stok dikembalikan')
                RiwCtrl-->>Browser: Redirect /admin/riwayat-transaksi
            end
        end
    end
```

---

## 3. Sequence Diagram — Edit Transaksi

```mermaid
sequenceDiagram
    title Sequence: Edit Transaksi (Ubah Item/Qty)

    actor Admin
    participant Browser
    participant Router
    participant RiwCtrl as RiwayatController
    participant TrxModel as Transaksi Model
    participant DetModel as DetailTransaksi Model
    participant BrgModel as Barang Model
    participant RstModel as Restock Model
    participant Session
    participant DB as Database

    %% Form Edit
    Admin->>Browser: Klik Edit pada transaksi
    Browser->>Router: GET /admin/riwayat-transaksi/edit/{id}
    Router->>RiwCtrl: edit(id)
    RiwCtrl->>RiwCtrl: requireRole('admin')
    RiwCtrl->>TrxModel: findById(id)
    TrxModel->>DB: SELECT transaksi
    DB-->>TrxModel: data

    alt Status = dibatalkan
        RiwCtrl-->>Browser: Error tidak bisa diedit
    else Status = selesai/diubah
        RiwCtrl->>DetModel: getItemsWithBarang(id)
        RiwCtrl->>BrgModel: getActive()
        RiwCtrl-->>Browser: Render form edit + items lama + barang aktif
    end

    %% Proses Update
    Admin->>Browser: Ubah qty / tambah-hapus item
    Admin->>Browser: Klik Simpan Perubahan
    Browser->>Router: POST /admin/riwayat-transaksi/update/{id}
    Router->>RiwCtrl: update(id)
    RiwCtrl->>RiwCtrl: requireRole('admin')

    RiwCtrl->>RiwCtrl: parseEditCart(cart_json)

    alt Keranjang baru kosong
        RiwCtrl-->>Browser: Error minimal 1 barang
    else Keranjang valid
        RiwCtrl->>DetModel: getByTransaksiId(id)
        DetModel-->>RiwCtrl: items lama

        RiwCtrl->>DB: beginTransaction()

        Note over RiwCtrl: STEP 1: Kembalikan stok lama
        loop Setiap item lama
            RiwCtrl->>BrgModel: increaseStock(id_barang, qty_lama)
            BrgModel->>DB: UPDATE stok = stok + qty
        end

        Note over RiwCtrl: STEP 2: Validasi & hitung item baru
        loop Setiap item baru
            RiwCtrl->>BrgModel: findActiveById(id_barang)
            RiwCtrl->>RiwCtrl: Cek stok >= qty baru
            RiwCtrl->>RstModel: getLastHargaBeli(id_barang)
            RiwCtrl->>RiwCtrl: Hitung subtotal & laba
        end

        Note over RiwCtrl: STEP 3: Kurangi stok baru
        loop Setiap item baru
            RiwCtrl->>BrgModel: decreaseStock(id_barang, qty_baru)
        end

        Note over RiwCtrl: STEP 4: Update database
        RiwCtrl->>DetModel: deleteByTransaksiId(id)
        loop Setiap item baru
            RiwCtrl->>DetModel: create(detail baru)
        end
        RiwCtrl->>TrxModel: updateTotals(id, totals baru)
        RiwCtrl->>TrxModel: updateMetodeBayar(id, metode)

        RiwCtrl->>DB: commit()
        RiwCtrl->>Session: setFlash('success', 'Transaksi berhasil diubah')
        RiwCtrl-->>Browser: Redirect /admin/riwayat-transaksi
    end
```

---

## 4. Activity Diagram — Batalkan Transaksi (Refund)

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart TD
    A([Start]) --> B[Admin buka halaman Riwayat Transaksi]
    B --> C[Pilih transaksi yang akan dibatalkan]
    C --> D{Status transaksi?}

    D -->|Dibatalkan| E[Error: Sudah dibatalkan sebelumnya]
    E --> Z([Stop])

    D -->|Selesai / Diubah| F[Input alasan pembatalan]
    F --> G[Klik Konfirmasi Batalkan]
    G --> H{Alasan kosong?}

    H -->|Ya| I[Error: Alasan wajib diisi]
    I --> Z

    H -->|Tidak| J[Begin Transaction]
    J --> K[Ambil detail item transaksi]
    K --> L[Loop per item:<br/>Kembalikan stok barang<br/>stok = stok + qty]
    L --> M[Update status = 'dibatalkan']
    M --> N[Simpan alasan pembatalan]
    N --> O[Commit Transaction]
    O --> P[Tampilkan pesan sukses:<br/>Transaksi dibatalkan,<br/>stok dikembalikan]
    P --> Q[Redirect ke Riwayat Transaksi]
    Q --> Z
```

---

## 5. Activity Diagram — Edit Transaksi

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart TD
    A([Start]) --> B[Admin buka halaman Riwayat Transaksi]
    B --> C[Klik Edit pada transaksi tertentu]
    C --> D{Status transaksi?}

    D -->|Dibatalkan| E[Error: Tidak bisa diedit]
    E --> Z([Stop])

    D -->|Selesai / Diubah| F[Sistem tampilkan form edit<br/>dengan items + qty saat ini]
    F --> G[Admin ubah qty / tambah / hapus item]
    G --> H[Klik Simpan Perubahan]
    H --> I{Keranjang baru kosong?}

    I -->|Ya| J[Error: Minimal 1 barang]
    J --> Z

    I -->|Tidak| K[Begin Transaction]

    K --> L[STEP 1: Kembalikan stok semua item lama]
    L --> M[STEP 2: Validasi item baru]
    M --> N{Semua barang aktif & stok cukup?}

    N -->|Tidak| O[Rollback - Tampilkan error]
    O --> Z

    N -->|Ya| P[STEP 3: Kurangi stok item baru]
    P --> Q[STEP 4: Hapus detail transaksi lama]
    Q --> R[STEP 5: Simpan detail transaksi baru]
    R --> S[STEP 6: Update total jual, beli, laba, nominal, kembalian]
    S --> T[Set status = 'diubah', edited_at = NOW]
    T --> U[Commit Transaction]
    U --> V[Tampilkan pesan sukses]
    V --> W[Redirect ke Riwayat Transaksi]
    W --> Z
```

---

## Catatan

- **Hanya Admin** yang bisa melihat riwayat, edit, dan batalkan transaksi
- **Pembatalan (Refund)**:
  - Wajib input alasan
  - Stok otomatis dikembalikan ke semua barang terkait
  - Transaksi yang sudah dibatalkan tidak bisa dibatalkan lagi
- **Edit Transaksi**:
  - Stok lama dikembalikan dulu, baru stok baru dikurangi
  - Harga dihitung ulang dari database (bukan input manual)
  - Status berubah jadi `diubah`, field `edited_at` ter-set
  - Transaksi yang sudah dibatalkan tidak bisa diedit
- Semua operasi menggunakan **database transaction** (atomik)
