# Sequence Diagram: Data Master

> Mencakup: Barang, Kategori, Supplier, User

```plantuml
@startuml
title Sequence Diagram - Data Master

actor Admin
participant Sistem
database DB

Admin -> Sistem: Login (username, password)
Sistem -> DB: Validasi kredensial
DB --> Sistem: Data user valid
Sistem --> Admin: Redirect ke Dashboard

Admin -> Sistem: Buka Menu Data Master
Admin -> Sistem: Pilih Entitas (Barang/Kategori/Supplier/User)
Sistem -> DB: Ambil daftar data
DB --> Sistem: Data entitas
Sistem --> Admin: Tampilkan Daftar Data

alt Tambah / Edit
  Admin -> Sistem: Submit Form Data
  Sistem -> Sistem: Validasi Input
  alt Validasi Gagal
    Sistem --> Admin: Tampilkan Pesan Error
  else Validasi Berhasil
    Sistem -> DB: Simpan / Update Data
    DB --> Sistem: Konfirmasi tersimpan
    Sistem --> Admin: Tampilkan Pesan Berhasil
  end
else Hapus
  Admin -> Sistem: Konfirmasi Hapus
  Sistem -> DB: Hapus Data
  DB --> Sistem: Konfirmasi terhapus
  Sistem --> Admin: Tampilkan Pesan Berhasil
end

Admin -> Sistem: Logout
Sistem --> Admin: Redirect ke Halaman Login
@enduml
```
