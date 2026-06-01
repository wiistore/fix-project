# Transaksi Penjualan (POS)

---

## 1. Use Case Diagram

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart LR
    Admin((Admin))
    Kasir((Kasir))

    subgraph SISTEM["Sistem POS Kopsis — Transaksi"]
        UC_CREATE[Buat Transaksi]
        UC_STRUK[/Lihat Struk/]
        UC_PDF[/Download PDF Struk/]
        UC_VALIDASI[/Validasi Keranjang/]
        UC_HITUNG[/Hitung Total dan Laba/]
        UC_KURANGI[/Kurangi Stok Barang/]
        UC_CEK_STOK[/Cek Ketersediaan Stok/]
        UC_BAYAR[/Validasi Pembayaran/]
    end

    %% INCLUDE
    UC_CREATE -->|include| UC_VALIDASI
    UC_CREATE -->|include| UC_CEK_STOK
    UC_CREATE -->|include| UC_HITUNG
    UC_CREATE -->|include| UC_BAYAR
    UC_CREATE -->|include| UC_KURANGI

    %% EXTEND
    UC_STRUK -.->|extend| UC_CREATE
    UC_PDF -.->|extend| UC_STRUK

    %% ACTOR
    Admin --> UC_CREATE
    Kasir --> UC_CREATE
```

### Keterangan Relasi

| Tipe | Use Case Utama | Target | Penjelasan |
|------|---------------|--------|------------|
| include | Buat Transaksi | Validasi Keranjang | Keranjang tidak boleh kosong |
| include | Buat Transaksi | Cek Ketersediaan Stok | Stok harus >= qty tiap item |
| include | Buat Transaksi | Hitung Total dan Laba | Hitung dari harga DB, bukan dari input |
| include | Buat Transaksi | Validasi Pembayaran | Cek metode valid & nominal cukup (cash) |
| include | Buat Transaksi | Kurangi Stok Barang | Stok dikurangi setelah transaksi valid |
| extend | Lihat Struk | Buat Transaksi | Opsional setelah transaksi berhasil |
| extend | Download PDF | Lihat Struk | Opsional jika user klik download |

---

## 2. Sequence Diagram — Buat Transaksi

```mermaid
sequenceDiagram
    title Sequence: Buat Transaksi Penjualan

    actor User as Admin/Kasir
    participant Browser as Browser (POS)
    participant Router
    participant TrxCtrl as TransaksiController
    participant TrxModel as Transaksi Model
    participant DetModel as DetailTransaksi Model
    participant BrgModel as Barang Model
    participant RstModel as Restock Model
    participant Session
    participant DB as Database

    %% Halaman POS
    User->>Browser: Buka halaman transaksi
    Browser->>Router: GET /kasir/transaksi atau /admin/transaksi
    Router->>TrxCtrl: kasirIndex() / adminIndex()
    TrxCtrl->>TrxCtrl: requireRole('kasir' / 'admin')
    TrxCtrl->>BrgModel: getActive()
    BrgModel->>DB: SELECT barang aktif
    DB-->>BrgModel: list barang
    TrxCtrl-->>Browser: Render halaman POS + daftar barang + metode bayar

    %% Checkout
    User->>Browser: Scan/pilih barang, atur qty
    User->>Browser: Pilih metode pembayaran
    Note over Browser: Metode: Cash, QRIS, Transfer, E-Wallet
    User->>Browser: Input nominal bayar (jika cash)
    User->>Browser: Klik Bayar

    Browser->>Router: POST /transaksi/store
    Router->>TrxCtrl: store()
    TrxCtrl->>TrxCtrl: requireRole(['admin','kasir'])
    TrxCtrl->>TrxCtrl: normalizeCart(cart_json)
    TrxCtrl->>TrxCtrl: validateTransactionInput(items, metode, nominal)

    alt Keranjang kosong / metode invalid
        TrxCtrl->>Session: setFlash('error', '...')
        TrxCtrl-->>Browser: Redirect kembali
    else Input valid
        TrxCtrl->>DB: beginTransaction()

        loop Setiap item di keranjang
            TrxCtrl->>BrgModel: findActiveById(id_barang)
            BrgModel->>DB: SELECT barang
            DB-->>BrgModel: data barang

            alt Barang tidak aktif
                TrxCtrl->>DB: rollBack()
                TrxCtrl-->>Browser: Error barang tidak valid
            else Barang aktif
                TrxCtrl->>TrxCtrl: Cek stok >= qty
                alt Stok tidak cukup
                    TrxCtrl->>DB: rollBack()
                    TrxCtrl-->>Browser: Error stok tidak cukup
                else Stok cukup
                    TrxCtrl->>RstModel: getLastHargaBeli(id_barang)
                    RstModel->>DB: SELECT harga_beli terakhir
                    DB-->>RstModel: harga_beli
                    TrxCtrl->>TrxCtrl: Hitung subtotal & laba item
                end
            end
        end

        alt Cash & nominal < total
            TrxCtrl->>DB: rollBack()
            TrxCtrl-->>Browser: Error nominal kurang
        else Nominal cukup / non-cash
            TrxCtrl->>TrxModel: generateCode()
            TrxModel-->>TrxCtrl: "TRX20260601..."

            TrxCtrl->>TrxModel: create(data transaksi)
            TrxModel->>DB: INSERT INTO transaksi
            DB-->>TrxModel: transaksi_id

            loop Setiap item
                TrxCtrl->>DetModel: create(detail item)
                DetModel->>DB: INSERT INTO detail_transaksi

                TrxCtrl->>BrgModel: decreaseStock(id, qty)
                BrgModel->>DB: UPDATE stok = stok - qty
            end

            TrxCtrl->>DB: commit()
            TrxCtrl->>Session: setFlash('success', 'Transaksi berhasil')
            TrxCtrl-->>Browser: Redirect ke halaman struk
        end
    end
```

---

## 3. Sequence Diagram — Lihat & Download Struk

```mermaid
sequenceDiagram
    title Sequence: Lihat Struk & Download PDF

    actor User as Admin/Kasir
    participant Browser
    participant Router
    participant TrxCtrl as TransaksiController
    participant TrxModel as Transaksi Model
    participant DetModel as DetailTransaksi Model
    participant DB as Database

    %% Lihat Struk
    User->>Browser: Klik lihat struk / redirect setelah transaksi
    Browser->>Router: GET /kasir/transaksi/struk/{id}
    Router->>TrxCtrl: kasirStruk(id)
    TrxCtrl->>TrxCtrl: requireRole('kasir')
    TrxCtrl->>TrxModel: findById(id)
    TrxModel->>DB: SELECT transaksi + nama kasir
    DB-->>TrxModel: data transaksi

    alt Kasir bukan pemilik transaksi
        TrxCtrl-->>Browser: Error akses ditolak
    else Pemilik valid
        TrxCtrl->>DetModel: getItemsWithBarang(id)
        DetModel->>DB: SELECT detail + nama barang
        DB-->>DetModel: list items
        TrxCtrl-->>Browser: Render halaman struk
    end

    %% Download PDF
    User->>Browser: Klik Download PDF
    Browser->>Router: GET /kasir/transaksi/pdf/{id}
    Router->>TrxCtrl: kasirPdf(id)
    TrxCtrl->>TrxModel: findById(id)
    TrxCtrl->>DetModel: getItemsWithBarang(id)
    TrxCtrl->>TrxCtrl: Render HTML struk-pdf.php
    TrxCtrl->>TrxCtrl: Generate PDF via Dompdf
    TrxCtrl-->>Browser: Download file struk-TRX...pdf
```

---

## 4. Activity Diagram — Buat Transaksi

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart TD
    A([Start]) --> B[Admin/Kasir buka halaman POS]
    B --> C[Sistem tampilkan daftar barang aktif]
    C --> D[Scan barcode / pilih barang]
    D --> E[Tentukan qty]
    E --> F[Barang masuk ke keranjang]
    F --> G{Tambah barang lagi?}
    G -->|Ya| D
    G -->|Tidak| H[Pilih metode pembayaran]

    H --> I{Metode = Cash?}
    I -->|Ya| J[Input nominal bayar]
    I -->|Tidak| K[Nominal = total otomatis]
    J --> L[Klik Bayar]
    K --> L

    L --> M{Keranjang kosong?}
    M -->|Ya| N[Error: Keranjang masih kosong]
    N --> Z([Stop])

    M -->|Tidak| O{Metode bayar valid?}
    O -->|Tidak| P[Error: Metode tidak valid]
    P --> Z

    O -->|Ya| Q[Begin Transaction]
    Q --> R[Loop per item keranjang]

    R --> S{Barang aktif di DB?}
    S -->|Tidak| T[Rollback - Error barang tidak valid]
    T --> Z

    S -->|Ya| U{Stok >= qty?}
    U -->|Tidak| V[Rollback - Error stok tidak cukup]
    V --> Z

    U -->|Ya| W[Ambil harga beli terakhir dari restock]
    W --> X[Hitung subtotal jual, subtotal beli, laba]
    X --> Y{Masih ada item?}
    Y -->|Ya| R

    Y -->|Tidak| AA{Cash & nominal < total?}
    AA -->|Ya| AB[Rollback - Error nominal kurang]
    AB --> Z

    AA -->|Tidak| AC[Generate kode transaksi unik]
    AC --> AD[Simpan transaksi utama]
    AD --> AE[Simpan detail per item]
    AE --> AF[Kurangi stok per item]
    AF --> AG[Commit Transaction]
    AG --> AH[Redirect ke halaman struk]
    AH --> Z
```

---

## Catatan

- **Kedua role** (Admin & Kasir) bisa membuat transaksi
- Harga dihitung ulang dari **database** (bukan dari input user) untuk keamanan
- Harga beli diambil dari **restock terakhir** untuk perhitungan laba
- Metode pembayaran: `cash`, `qris`, `transfer`, `ewallet`
- Kasir hanya bisa lihat struk transaksi **miliknya sendiri**
- Admin bisa lihat struk **semua transaksi**
- Semua operasi dalam **1 database transaction** (atomik)
