# Activity Diagram: Data Master

> Mencakup: Barang, Kategori, Supplier, User

```plantuml
@startuml
title Activity Diagram - Data Master

|Admin|
start
:Login;
:Pilih Menu Data Master;
:Pilih Entitas\n(Barang / Kategori / Supplier / User);

|Sistem|
:Tampilkan Daftar Data;

|Admin|
switch (Pilih Aksi)
case (Tambah / Edit)
  :Isi Form Data;
  |Sistem|
  if (Validasi Input) then (Berhasil)
    :Simpan Data;
    :Tampilkan Pesan Berhasil;
  else (Gagal)
    |Admin|
    :Isi Form Data;
    detach
  endif
case (Hapus)
  :Konfirmasi Hapus;
  |Sistem|
  :Hapus Data;
  :Tampilkan Pesan Berhasil;
endswitch

|Admin|
:Logout;
stop
@enduml
```
