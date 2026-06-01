# Dokumentasi UML — Kopsis POS

Sistem Point of Sale (POS) untuk Koperasi Siswa.
Semua diagram menggunakan format **Mermaid** — bisa langsung di-render di GitHub, [Mermaid Live Editor](https://mermaid.live/), draw.io, atau VS Code.

---

## Struktur Dokumen (Per Fitur)

| No | File | Fitur | Isi |
|----|------|-------|-----|
| 1 | [data-master.md](data-master.md) | **Data Master** (Barang, Kategori, Supplier, User) | Use Case, Sequence, Activity |
| 2 | [restock.md](restock.md) | **Restock** (Stok Masuk & Keluar) | Use Case, Sequence, Activity |
| 3 | [transaksi.md](transaksi.md) | **Transaksi Penjualan** (POS) | Use Case, Sequence, Activity |
| 4 | [laporan.md](laporan.md) | **Laporan** (Penjualan, Laba, Terlaris, Restock, Export) | Use Case, Sequence, Activity |
| 5 | [riwayat-transaksi.md](riwayat-transaksi.md) | **Riwayat Transaksi** (Refund & Edit) | Use Case, Sequence, Activity |

---

## Diagram Tambahan

| No | File | Tipe Diagram | Keterangan |
|----|------|-------------|------------|
| 6 | [erd.md](erd.md) | **ERD** | Entity Relationship Diagram + penjelasan relasi & constraint |
| 7 | [dfd.md](dfd.md) | **DFD Level 0 & 1** | Context Diagram + Data Flow Detail |
| 8 | [class-diagram.md](class-diagram.md) | **Class Diagram** | Core, Controllers, Models, Middleware |

---

## Cara Render / Edit

### GitHub
Otomatis render. Langsung buka file `.md` di GitHub dan diagram muncul.

### Mermaid Live Editor (Rekomendasi untuk edit)
1. Buka [mermaid.live](https://mermaid.live/)
2. Paste kode Mermaid dari file
3. Edit langsung di browser
4. **Save gambar**: Actions → Export PNG / SVG

### VS Code
Install extension **"Markdown Preview Mermaid Support"** → preview langsung.

### draw.io
Import Mermaid via menu **Extras → Edit Diagram** (paste kode).

---

## Catatan Penting Sistem

| Rule | Keterangan |
|------|------------|
| Kasir hanya bisa reset password | Username & email TIDAK bisa diedit oleh kasir |
| Pembatalan transaksi | Hanya Admin, wajib input alasan, stok otomatis dikembalikan |
| Edit transaksi | Hanya Admin, stok lama dikembalikan lalu stok baru dikurangi |
| Restock keluar | Wajib input alasan pengurangan stok |
| Protected user | Admin utama (is_protected=1) tidak bisa diedit/dihapus |
| Database transaction | Semua operasi stok berjalan atomik (commit/rollback) |
