# Flowchart: Transaksi

```plantuml
@startuml
title Flowchart - Transaksi

skinparam linetype ortho
skinparam shadowing false
skinparam nodesep 25
skinparam ranksep 30
skinparam ActivityBackgroundColor #FFF8DC
skinparam ActivityBorderColor #333
skinparam ActivityDiamondBackgroundColor #FFE4B5

start
:Login;
:Pilih Menu Transaksi;
:Tampilkan Halaman Transaksi;
:Pilih Barang;
:Tampilkan Detail Barang & Harga;
:Input Jumlah Barang;
:Hitung Total Pembayaran;
:Pilih Metode Pembayaran;

if (Pembayaran Valid?) then (Tidak)
  :Tampilkan Pesan Error;
  stop
else (Ya)
  :Simpan Data Transaksi;
  :Simpan Detail Transaksi;
  :Update Stok Barang;
  :Cetak Nota / Struk;
endif

:Logout;
stop

@enduml
```
