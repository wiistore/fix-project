# Dokumentasi UML - Kopsis POS

Sistem Point of Sale (POS) untuk Koperasi Siswa.

## Daftar Diagram

### Use Case Diagram
| File | Format | Deskripsi |
|------|--------|-----------|
| [use-case-diagram.puml](use-case-diagram.puml) | PlantUML | Use case lengkap untuk aktor Admin dan Kasir |

### Sequence Diagram
| File | Format | Deskripsi |
|------|--------|-----------|
| [sequence-diagram-login.puml](sequence-diagram-login.puml) | PlantUML | Alur proses login |
| [sequence-diagram-transaksi.puml](sequence-diagram-transaksi.puml) | PlantUML | Alur proses transaksi penjualan (POS) |
| [sequence-diagram-restock.puml](sequence-diagram-restock.puml) | PlantUML | Alur proses restock barang (masuk/keluar) |
| [sequence-diagram-kasir-profil.puml](sequence-diagram-kasir-profil.puml) | PlantUML | Alur reset password kasir di halaman profil |

### Activity Diagram
| File | Format | Deskripsi |
|------|--------|-----------|
| [activity-diagram-login.puml](activity-diagram-login.puml) | PlantUML | Flowchart proses login |
| [activity-diagram-transaksi.puml](activity-diagram-transaksi.puml) | PlantUML | Flowchart proses transaksi penjualan |
| [activity-diagram-restock.puml](activity-diagram-restock.puml) | PlantUML | Flowchart proses restock stok masuk/keluar |
| [activity-diagram-kasir-profil.puml](activity-diagram-kasir-profil.puml) | PlantUML | Flowchart reset password kasir |
| [activity-diagram-batalkan-transaksi.puml](activity-diagram-batalkan-transaksi.puml) | PlantUML | Flowchart pembatalan (refund) transaksi |

### Class Diagram
| File | Format | Deskripsi |
|------|--------|-----------|
| [class-diagram.puml](class-diagram.puml) | PlantUML | Struktur class lengkap (Core, Controllers, Models, Middleware) |

### Entity Relationship Diagram (ERD)
| File | Format | Deskripsi |
|------|--------|-----------|
| [erd.md](erd.md) | Mermaid | ERD database dengan penjelasan relasi dan constraint |

## Cara Render

### PlantUML (.puml)
1. **Online**: Paste kode ke [plantuml.com/plantuml](https://www.plantuml.com/plantuml/uml/)
2. **VS Code**: Install extension "PlantUML" lalu preview dengan `Alt+D`
3. **CLI**: `java -jar plantuml.jar docs/*.puml`

### Mermaid (.md)
1. **GitHub**: Otomatis render di preview markdown GitHub
2. **VS Code**: Install extension "Markdown Preview Mermaid Support"
3. **Online**: Paste ke [mermaid.live](https://mermaid.live/)

## Catatan Penting

- **Profil Kasir**: Kasir hanya bisa reset password sendiri. Username dan email TIDAK bisa diedit oleh kasir (hanya Admin via menu User Management).
- **Pembatalan Transaksi**: Hanya Admin yang bisa membatalkan. Stok otomatis dikembalikan.
- **Restock Keluar**: Wajib mengisi alasan pengurangan stok.
- **Protected User**: Admin utama (is_protected=1) tidak bisa diedit/dihapus dari menu user.
