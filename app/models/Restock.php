<?php

declare(strict_types=1);

class Restock extends Model
{
    private $table = 'restock';

    public function getAll(int $limit = 100): array
    {
        // Ambil data restock (LEFT JOIN supplier karena bisa null untuk tipe keluar)
        $limit = max(1, min($limit, 200));

        $sql = "
            SELECT
                r.id,
                r.tanggal,
                r.tipe,
                r.id_barang,
                b.kode_barang,
                b.nama AS nama_barang,
                b.satuan,
                r.id_supplier,
                COALESCE(s.nama, '-') AS nama_supplier,
                r.id_user,
                u.username AS dibuat_oleh,
                r.qty,
                r.harga_beli,
                r.harga_jual_baru,
                r.total_nilai,
                r.catatan,
                r.alasan,
                r.created_at
            FROM {$this->table} r
            INNER JOIN barang b ON b.id = r.id_barang
            LEFT JOIN supplier s ON s.id = r.id_supplier
            INNER JOIN users u ON u.id = r.id_user
            ORDER BY r.tanggal DESC, r.id DESC
            LIMIT {$limit}
        ";

        return $this->fetchAll($sql);
    }

    public function findById(int $id)
    {
        // Detail restock
        $sql = "
            SELECT
                r.id,
                r.tanggal,
                r.tipe,
                r.id_barang,
                b.kode_barang,
                b.nama AS nama_barang,
                b.satuan,
                r.id_supplier,
                COALESCE(s.nama, '-') AS nama_supplier,
                r.id_user,
                u.username AS dibuat_oleh,
                r.qty,
                r.harga_beli,
                r.harga_jual_baru,
                r.total_nilai,
                r.catatan,
                r.alasan,
                r.created_at
            FROM {$this->table} r
            INNER JOIN barang b ON b.id = r.id_barang
            LEFT JOIN supplier s ON s.id = r.id_supplier
            INNER JOIN users u ON u.id = r.id_user
            WHERE r.id = :id
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'id' => $id,
        ]);
    }

    public function create(array $data): int
    {
        // Simpan restock
        $sql = "
            INSERT INTO {$this->table}
                (
                    tipe,
                    tanggal,
                    id_barang,
                    id_supplier,
                    id_user,
                    qty,
                    harga_beli,
                    harga_jual_baru,
                    total_nilai,
                    catatan,
                    alasan
                )
            VALUES
                (
                    :tipe,
                    :tanggal,
                    :id_barang,
                    :id_supplier,
                    :id_user,
                    :qty,
                    :harga_beli,
                    :harga_jual_baru,
                    :total_nilai,
                    :catatan,
                    :alasan
                )
        ";

        $qty = (int) $data['qty'];
        $hargaBeli = (float) $data['harga_beli'];
        $totalNilai = $qty * $hargaBeli;

        $this->execute($sql, [
            'tipe' => $data['tipe'] ?? 'masuk',
            'tanggal' => $data['tanggal'] ?? date('Y-m-d'),
            'id_barang' => (int) $data['id_barang'],
            'id_supplier' => $data['id_supplier'] !== null && $data['id_supplier'] !== '' ? (int) $data['id_supplier'] : null,
            'id_user' => (int) $data['id_user'],
            'qty' => $qty,
            'harga_beli' => $hargaBeli,
            'harga_jual_baru' => $this->nullableNumber($data['harga_jual_baru'] ?? null),
            'total_nilai' => $totalNilai,
            'catatan' => $this->nullableText($data['catatan'] ?? ''),
            'alasan' => $this->nullableText($data['alasan'] ?? ''),
        ]);

        return $this->lastInsertId();
    }

    public function getFiltered(?string $start = null, ?string $end = null, ?string $tipe = null, int $limit = 200): array
    {
        // Filter tanggal + tipe
        $limit = max(1, min($limit, 500));

        $sql = "
            SELECT
                r.id,
                r.tanggal,
                r.tipe,
                r.id_barang,
                b.kode_barang,
                b.nama AS nama_barang,
                b.satuan,
                r.id_supplier,
                COALESCE(s.nama, '-') AS nama_supplier,
                r.id_user,
                u.username AS dibuat_oleh,
                r.qty,
                r.harga_beli,
                r.harga_jual_baru,
                r.total_nilai,
                r.catatan,
                r.alasan,
                r.created_at
            FROM {$this->table} r
            INNER JOIN barang b ON b.id = r.id_barang
            LEFT JOIN supplier s ON s.id = r.id_supplier
            INNER JOIN users u ON u.id = r.id_user
            WHERE 1 = 1
        ";

        $params = [];

        if ($start !== null && $start !== '') {
            $sql .= " AND r.tanggal >= :tanggal_mulai";
            $params['tanggal_mulai'] = $start;
        }

        if ($end !== null && $end !== '') {
            $sql .= " AND r.tanggal <= :tanggal_selesai";
            $params['tanggal_selesai'] = $end;
        }

        if ($tipe !== null && $tipe !== '' && in_array($tipe, ['masuk', 'keluar'], true)) {
            $sql .= " AND r.tipe = :tipe";
            $params['tipe'] = $tipe;
        }

        $sql .= "
            ORDER BY r.tanggal DESC, r.id DESC
            LIMIT {$limit}
        ";

        return $this->fetchAll($sql, $params);
    }

    public function getLastHargaBeli(int $barangId): float
    {
        // Harga beli terakhir buat transaksi nanti (hanya dari tipe masuk)
        $sql = "
            SELECT harga_beli
            FROM {$this->table}
            WHERE id_barang = :id_barang
              AND tipe = 'masuk'
            ORDER BY tanggal DESC, id DESC
            LIMIT 1
        ";

        $harga = $this->query($sql, [
            'id_barang' => $barangId,
        ])->fetchColumn();

        return $harga === false ? 0.0 : (float) $harga;
    }

    public function summary(?string $start = null, ?string $end = null, ?string $tipe = null): array
    {
        // Ringkasan restock
        $sql = "
            SELECT
                COUNT(r.id) AS total_restock,
                COALESCE(SUM(CASE WHEN r.tipe = 'masuk' THEN r.qty ELSE 0 END), 0) AS total_qty_masuk,
                COALESCE(SUM(CASE WHEN r.tipe = 'keluar' THEN r.qty ELSE 0 END), 0) AS total_qty_keluar,
                COALESCE(SUM(r.qty), 0) AS total_qty,
                COALESCE(SUM(r.total_nilai), 0) AS total_nilai
            FROM {$this->table} r
            WHERE 1 = 1
        ";

        $params = [];

        if ($start !== null && $start !== '') {
            $sql .= " AND r.tanggal >= :tanggal_mulai";
            $params['tanggal_mulai'] = $start;
        }

        if ($end !== null && $end !== '') {
            $sql .= " AND r.tanggal <= :tanggal_selesai";
            $params['tanggal_selesai'] = $end;
        }

        if ($tipe !== null && $tipe !== '' && in_array($tipe, ['masuk', 'keluar'], true)) {
            $sql .= " AND r.tipe = :tipe";
            $params['tipe'] = $tipe;
        }

        $row = $this->fetch($sql, $params);

        return [
            'total_restock' => (int) ($row['total_restock'] ?? 0),
            'total_qty_masuk' => (int) ($row['total_qty_masuk'] ?? 0),
            'total_qty_keluar' => (int) ($row['total_qty_keluar'] ?? 0),
            'total_qty' => (int) ($row['total_qty'] ?? 0),
            'total_nilai' => (float) ($row['total_nilai'] ?? 0),
        ];
    }

    public function countAll(): int
    {
        $sql = "
            SELECT COUNT(id)
            FROM {$this->table}
        ";

        return $this->countRows($sql);
    }

    public function getPaginated(int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT
                r.id,
                r.tanggal,
                r.tipe,
                r.id_barang,
                b.kode_barang,
                b.nama AS nama_barang,
                b.satuan,
                r.id_supplier,
                COALESCE(s.nama, '-') AS nama_supplier,
                r.id_user,
                u.username AS dibuat_oleh,
                r.qty,
                r.harga_beli,
                r.harga_jual_baru,
                r.total_nilai,
                r.catatan,
                r.alasan,
                r.created_at
            FROM {$this->table} r
            INNER JOIN barang b ON b.id = r.id_barang
            LEFT JOIN supplier s ON s.id = r.id_supplier
            INNER JOIN users u ON u.id = r.id_user
            ORDER BY r.tanggal DESC, r.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        return $this->fetchAll($sql);
    }

    private function nullableText($value)
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableNumber($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
