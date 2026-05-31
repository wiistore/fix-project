<?php

declare(strict_types=1);

class Transaksi extends Model
{
    private $table = 'transaksi';

    public function create(array $data): int
    {
        // Simpan transaksi utama
        $sql = "
            INSERT INTO {$this->table}
                (
                    kode_transaksi,
                    id_user,
                    tanggal,
                    total_jual,
                    total_beli,
                    total_laba,
                    metode_bayar,
                    nominal_bayar,
                    kembalian
                )
            VALUES
                (
                    :kode_transaksi,
                    :id_user,
                    :tanggal,
                    :total_jual,
                    :total_beli,
                    :total_laba,
                    :metode_bayar,
                    :nominal_bayar,
                    :kembalian
                )
        ";

        $this->execute($sql, [
            'kode_transaksi' => $data['kode_transaksi'],
            'id_user' => (int) $data['id_user'],
            'tanggal' => $data['tanggal'] ?? date('Y-m-d H:i:s'),
            'total_jual' => (float) $data['total_jual'],
            'total_beli' => (float) $data['total_beli'],
            'total_laba' => (float) $data['total_laba'],
            'metode_bayar' => $data['metode_bayar'],
            'nominal_bayar' => (float) $data['nominal_bayar'],
            'kembalian' => (float) $data['kembalian'],
        ]);

        return $this->lastInsertId();
    }

    public function findById(int $id)
    {
        // Detail transaksi utama
        $sql = "
            SELECT
                t.id,
                t.kode_transaksi,
                t.id_user,
                u.username AS nama_kasir,
                t.tanggal,
                t.total_jual,
                t.total_beli,
                t.total_laba,
                t.metode_bayar,
                t.nominal_bayar,
                t.kembalian,
                t.status,
                t.alasan_batal,
                t.edited_at,
                t.created_at
            FROM {$this->table} t
            INNER JOIN users u ON u.id = t.id_user
            WHERE t.id = :id
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'id' => $id,
        ]);
    }

    public function getByDateRange(?string $start = null, ?string $end = null, int $limit = 300): array
    {
        // Filter transaksi
        $limit = max(1, min($limit, 500));

        $sql = "
            SELECT
                t.id,
                t.kode_transaksi,
                t.id_user,
                u.username AS nama_kasir,
                t.tanggal,
                t.total_jual,
                t.total_beli,
                t.total_laba,
                t.metode_bayar,
                t.nominal_bayar,
                t.kembalian,
                t.status,
                t.alasan_batal,
                t.edited_at,
                t.created_at
            FROM {$this->table} t
            INNER JOIN users u ON u.id = t.id_user
            WHERE 1 = 1
        ";

        $params = [];

        if ($start !== null && $start !== '') {
            $sql .= " AND DATE(t.tanggal) >= :tanggal_mulai";
            $params['tanggal_mulai'] = $start;
        }

        if ($end !== null && $end !== '') {
            $sql .= " AND DATE(t.tanggal) <= :tanggal_selesai";
            $params['tanggal_selesai'] = $end;
        }

        $sql .= "
            ORDER BY t.tanggal DESC, t.id DESC
            LIMIT {$limit}
        ";

        return $this->fetchAll($sql, $params);
    }

    public function generateCode(): string
    {
        // Kode transaksi unik
        do {
            $code = 'TRX' . date('YmdHis') . random_int(100, 999);
        } while ($this->kodeExists($code));

        return $code;
    }

    public function kodeExists(string $kode): bool
    {
        $sql = "
            SELECT id
            FROM {$this->table}
            WHERE kode_transaksi = :kode_transaksi
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'kode_transaksi' => $kode,
        ]) !== false;
    }

    public function updateStatus(int $id, string $status, ?string $alasanBatal = null): bool
    {
        // Update status transaksi
        if (!in_array($status, ['selesai', 'diubah', 'dibatalkan'], true)) {
            return false;
        }

        $sql = "
            UPDATE {$this->table}
            SET status = :status,
                alasan_batal = :alasan_batal
            WHERE id = :id
            LIMIT 1
        ";

        return $this->execute($sql, [
            'status' => $status,
            'alasan_batal' => $alasanBatal,
            'id' => $id,
        ]);
    }

    public function updateTotals(int $id, array $totals): bool
    {
        // Update total transaksi setelah edit
        $sql = "
            UPDATE {$this->table}
            SET total_jual = :total_jual,
                total_beli = :total_beli,
                total_laba = :total_laba,
                nominal_bayar = :nominal_bayar,
                kembalian = :kembalian,
                status = 'diubah',
                edited_at = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        return $this->execute($sql, [
            'total_jual' => (float) $totals['total_jual'],
            'total_beli' => (float) $totals['total_beli'],
            'total_laba' => (float) $totals['total_laba'],
            'nominal_bayar' => (float) $totals['nominal_bayar'],
            'kembalian' => (float) $totals['kembalian'],
            'id' => $id,
        ]);
    }

    public function isValidPaymentMethod(string $method): bool
    {
        return in_array($method, ['cash', 'transfer', 'qris', 'ewallet'], true);
    }

    public function updateMetodeBayar(int $id, string $metodeBayar): bool
    {
        if (!$this->isValidPaymentMethod($metodeBayar)) {
            return false;
        }

        $sql = "
            UPDATE {$this->table}
            SET metode_bayar = :metode_bayar
            WHERE id = :id
            LIMIT 1
        ";

        return $this->execute($sql, [
            'metode_bayar' => $metodeBayar,
            'id' => $id,
        ]);
    }

    public function getPaginated(int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT
                t.id,
                t.kode_transaksi,
                t.id_user,
                u.username AS nama_kasir,
                t.tanggal,
                t.total_jual,
                t.total_beli,
                t.total_laba,
                t.metode_bayar,
                t.nominal_bayar,
                t.kembalian,
                t.status,
                t.alasan_batal,
                t.edited_at,
                t.created_at
            FROM {$this->table} t
            INNER JOIN users u ON u.id = t.id_user
            ORDER BY t.tanggal DESC, t.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        return $this->fetchAll($sql);
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