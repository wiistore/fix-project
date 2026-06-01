# Dokumentasi UML - Kopsis POS

Sistem Point of Sale (POS) untuk Koperasi Siswa.
Semua diagram menggunakan format **Mermaid** agar mudah diedit dan di-render langsung di GitHub, Mermaid Live Editor, atau VS Code.

## Daftar Diagram

### Use Case Diagram
| File | Deskripsi |
|------|-----------|
| [use-case-diagram.md](use-case-diagram.md) | Use case lengkap untuk aktor Admin dan Kasir (include & extend) |

### Sequence Diagram
| File | Deskripsi |
|------|-----------|
| [sequence-diagram-login.md](sequence-diagram-login.md) | Alur proses login |
| [sequence-diagram-transaksi.md](sequence-diagram-transaksi.md) | Alur proses transaksi penjualan (POS) |
| [sequence-diagram-restock.md](sequence-diagram-restock.md) | Alur proses restock barang (masuk/keluar) |
| [sequence-diagram-kasir-profil.md](sequence-diagram-kasir-profil.md) | Alur reset password kasir di halaman profil |

### Activity Diagram
| File | Deskripsi |
|------|-----------|
| [activity-diagram-login.md](activity-diagram-login.md) | Flowchart proses login |
| [activity-diagram-transaksi.md](activity-diagram-transaksi.md) | Flowchart proses transaksi penjualan |
| [activity-diagram-restock.md](activity-diagram-restock.md) | Flowchart proses restock stok masuk/keluar |
| [activity-diagram-kasir-profil.md](activity-diagram-kasir-profil.md) | Flowchart reset password kasir |
| [activity-diagram-batalkan-transaksi.md](activity-diagram-batalkan-transaksi.md) | Flowchart pembatalan (refund) transaksi |

### Class Diagram
| File | Deskripsi |
|------|-----------|
| [class-diagram.md](class-diagram.md) | Struktur class lengkap (Core, Controllers, Models, Middleware) |

### Entity Relationship Diagram (ERD)
| File | Deskripsi |
|------|-----------|
| [erd.md](erd.md) | ERD database dengan penjelasan relasi dan constraint |

## Cara Render

### GitHub
Otomatis! GitHub render Mermaid di preview markdown langsung.

### Mermaid Live Editor (Online)
1. Buka [mermaid.live](https://mermaid.live/)
2. Paste kode Mermaid dari file `.md`
3. Edit langsung, save gambar (PNG/SVG)

### VS Code
Install extension **"Markdown Preview Mermaid Support"** → preview langsung di editor.

### draw.io
Mermaid bisa di-import ke draw.io via menu **Extras > Edit Diagram** (paste kode).

## Catatan Penting

- **Profil Kasir**: Kasir hanya bisa reset password sendiri. Username dan email TIDAK bisa diedit oleh kasir (hanya Admin via menu User Management).
- **Pembatalan Transaksi**: Hanya Admin yang bisa membatalkan. Stok otomatis dikembalikan.
- **Restock Keluar**: Wajib mengisi alasan pengurangan stok.
- **Protected User**: Admin utama (is_protected=1) tidak bisa diedit/dihapus dari menu user.
