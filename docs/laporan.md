# Laporan

---

## 1. Use Case Diagram

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart LR
    Admin((Admin))

    subgraph SISTEM["Sistem POS Kopsis — Laporan"]
        UC_INDEX[Lihat Ringkasan Laporan]
        UC_JUAL[Laporan Penjualan]
        UC_LABA[Laporan Laba]
        UC_TOP[Laporan Barang Terlaris]
        UC_RESTOCK[Laporan Restock]
        UC_EXPORT[/Export Laporan CSV/]
        UC_FILTER[/Filter Periode Tanggal/]
    end

    %% INCLUDE
    UC_JUAL -->|include| UC_FILTER
    UC_LABA -->|include| UC_FILTER
    UC_TOP -->|include| UC_FILTER
    UC_RESTOCK -->|include| UC_FILTER

    %% EXTEND
    UC_EXPORT -.->|extend| UC_INDEX
    UC_EXPORT -.->|extend| UC_JUAL
    UC_EXPORT -.->|extend| UC_LABA
    UC_EXPORT -.->|extend| UC_TOP
    UC_EXPORT -.->|extend| UC_RESTOCK

    %% ACTOR
    Admin --> UC_INDEX
    Admin --> UC_JUAL
    Admin --> UC_LABA
    Admin --> UC_TOP
    Admin --> UC_RESTOCK
```

### Keterangan Relasi

| Tipe | Use Case Utama | Target | Penjelasan |
|------|---------------|--------|------------|
| include | Semua laporan detail | Filter Periode | Wajib filter tanggal mulai & selesai |
| extend | Export CSV | Semua laporan | Opsional, jika admin klik tombol export |

---

## 2. Sequence Diagram — Lihat Laporan Penjualan

```mermaid
sequenceDiagram
    title Sequence: Lihat Laporan Penjualan

    actor Admin
    participant Browser
    participant Router
    participant LapCtrl as LaporanController
    participant LapModel as Laporan Model
    participant Session
    participant DB as Database

    Admin->>Browser: Buka /admin/laporan/penjualan
    Browser->>Router: GET /admin/laporan/penjualan
    Router->>LapCtrl: penjualan()
    LapCtrl->>LapCtrl: requireRole('admin')

    Note over LapCtrl: Ambil filter dari query string<br/>?tanggal_mulai=...&tanggal_selesai=...

    LapCtrl->>LapModel: penjualan(start, end)
    LapModel->>DB: SELECT transaksi + detail<br/>WHERE tanggal BETWEEN ? AND ?
    DB-->>LapModel: data penjualan

    LapCtrl-->>Browser: Render halaman laporan penjualan
```

---

## 3. Sequence Diagram — Export Laporan CSV

```mermaid
sequenceDiagram
    title Sequence: Export Laporan ke CSV

    actor Admin
    participant Browser
    participant Router
    participant LapCtrl as LaporanController
    participant LapModel as Laporan Model
    participant DB as Database

    Admin->>Browser: Klik tombol Export pada halaman laporan
    Browser->>Router: GET /admin/laporan/export/penjualan?tanggal_mulai=...&tanggal_selesai=...
    Router->>LapCtrl: exportPenjualan()
    LapCtrl->>LapCtrl: requireRole('admin')

    LapCtrl->>LapModel: penjualan(start, end)
    LapModel->>DB: SELECT data laporan
    DB-->>LapModel: data

    LapCtrl->>LapCtrl: Set header Content-Type: text/csv
    LapCtrl->>LapCtrl: Generate CSV rows dari data
    LapCtrl-->>Browser: Download file laporan-penjualan.csv
```

---

## 4. Activity Diagram — Lihat Laporan

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart TD
    A([Start]) --> B[Admin buka halaman Laporan]
    B --> C{Pilih jenis laporan}

    C -->|Ringkasan| D1[Tampilkan ringkasan:<br/>total transaksi, penjualan, laba]
    C -->|Penjualan| D2[Buka halaman Laporan Penjualan]
    C -->|Laba| D3[Buka halaman Laporan Laba]
    C -->|Barang Terlaris| D4[Buka halaman Barang Terlaris]
    C -->|Restock| D5[Buka halaman Laporan Restock]

    D2 --> E[Input filter periode tanggal]
    D3 --> E
    D4 --> E
    D5 --> E

    E --> F[Sistem query data sesuai filter]
    F --> G[Tampilkan tabel/grafik laporan]

    D1 --> H{Mau export?}
    G --> H

    H -->|Ya| I[Klik tombol Export]
    I --> J[Sistem generate file CSV]
    J --> K[Download CSV ke browser]
    K --> Z([Stop])

    H -->|Tidak| Z
```

---

## 5. Activity Diagram — Export CSV

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart TD
    A([Start]) --> B[Admin di halaman laporan]
    B --> C[Klik tombol Export]
    C --> D[Sistem ambil parameter filter tanggal]
    D --> E[Query data laporan dari database]
    E --> F{Data kosong?}

    F -->|Ya| G[Generate CSV dengan header saja]
    F -->|Tidak| H[Generate CSV header + rows data]

    G --> I[Set response header Content-Type: text/csv]
    H --> I

    I --> J[Stream/download file CSV ke browser]
    J --> Z([Stop])
```

---

## Catatan

- Laporan **hanya bisa diakses Admin**
- Jenis laporan yang tersedia:
  - **Ringkasan**: overview total transaksi, penjualan, modal, laba
  - **Penjualan**: detail per transaksi dalam periode
  - **Laba**: laba per transaksi/per hari
  - **Barang Terlaris**: ranking barang berdasarkan qty terjual
  - **Restock**: riwayat stok masuk/keluar dalam periode
- Export tersedia untuk **semua jenis laporan** dalam format CSV
- Filter periode menggunakan `tanggal_mulai` dan `tanggal_selesai`
