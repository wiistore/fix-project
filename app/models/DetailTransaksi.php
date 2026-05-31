<?php

declare(strict_types=1);

class DetailTransaksi extends Model
{
    private $table = 'detail_transaksi';

    public function create(array $data): int
    {
        // Simpan detail transaksi
        $sql = "
            INSERT INTO {$this->table}
                (
                    id_transaksi,
                    id_barang,
                    qty,
                    harga_jual,
                    harga_beli,
                    subtotal_jual,
                    subtotal_beli,
                    laba_item
                )
            VALUES
                (
                    :id_transaksi,
                    :id_barang,
                    :qty,
                    :harga_jual,
                    :harga_beli,
                    :subtotal_jual,
                    :subtotal_beli,
                    :laba_item
                )
        ";

        $this->execute($sql, [
            'id_transaksi' => (int) $data['id_transaksi'],
            'id_barang' => (int) $data['id_barang'],
            'qty' => (int) $data['qty'],
            'harga_jual' => (float) $data['harga_jual'],
            'harga_beli' => (float) $data['harga_beli'],
            'subtotal_jual' => (float) $data['subtotal_jual'],
            'subtotal_beli' => (float) $data['subtotal_beli'],
            'laba_item' => (float) $data['laba_item'],
        ]);

        return $this->lastInsertId();
    }

    public function getByTransaksiId(int $transaksiId): array
    {
        // Detail item transaksi
        $sql = "
            SELECT
                id,
                id_transaksi,
                id_barang,
                qty,
                harga_jual,
                harga_beli,
                subtotal_jual,
                subtotal_beli,
                laba_item
            FROM {$this->table}
            WHERE id_transaksi = :id_transaksi
            ORDER BY id ASC
        ";

        return $this->fetchAll($sql, [
            'id_transaksi' => $transaksiId,
        ]);
    }

    public function getItemsWithBarang(int $transaksiId): array
    {
        // Detail transaksi + barang
        $sql = "
            SELECT
                dt.id,
                dt.id_transaksi,
                dt.id_barang,
                b.kode_barang,
                b.barcode,
                b.nama AS nama_barang,
                b.satuan,
                dt.qty,
                dt.harga_jual,
                dt.harga_beli,
                dt.subtotal_jual,
                dt.subtotal_beli,
                dt.laba_item
            FROM {$this->table} dt
            INNER JOIN barang b ON b.id = dt.id_barang
            WHERE dt.id_transaksi = :id_transaksi
            ORDER BY dt.id ASC
        ";

        return $this->fetchAll($sql, [
            'id_transaksi' => $transaksiId,
        ]);
    }

    public function summaryByTransaksiId(int $transaksiId): array
    {
        // Ringkasan detail transaksi
        $sql = "
            SELECT
                COUNT(id) AS total_item,
                COALESCE(SUM(qty), 0) AS total_qty,
                COALESCE(SUM(subtotal_jual), 0) AS total_jual,
                COALESCE(SUM(subtotal_beli), 0) AS total_beli,
                COALESCE(SUM(laba_item), 0) AS total_laba
            FROM {$this->table}
            WHERE id_transaksi = :id_transaksi
        ";

        $row = $this->fetch($sql, [
            'id_transaksi' => $transaksiId,
        ]);

        return [
            'total_item' => (int) ($row['total_item'] ?? 0),
            'total_qty' => (int) ($row['total_qty'] ?? 0),
            'total_jual' => (float) ($row['total_jual'] ?? 0),
            'total_beli' => (float) ($row['total_beli'] ?? 0),
            'total_laba' => (float) ($row['total_laba'] ?? 0),
        ];
    }

    public function deleteByTransaksiId(int $transaksiId): bool
    {
        // Hapus semua detail transaksi (untuk edit/replace)
        $sql = "
            DELETE FROM {$this->table}
            WHERE id_transaksi = :id_transaksi
        ";

        return $this->execute($sql, [
            'id_transaksi' => $transaksiId,
        ]);
    }

    public function countAll(): int
    {
        $sql = "
            SELECT COUNT(id)
            FROM {$this->table}
        ";

        return $this->countRows($sql);
    }
}