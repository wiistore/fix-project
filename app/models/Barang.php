<?php

declare(strict_types=1);

class Barang extends Model
{
    private $table = 'barang';

    public function getAll(): array
    {
        // Ambil data barang
        $sql = "
            SELECT 
                b.id,
                b.kode_barang,
                b.barcode,
                b.nama,
                b.id_kategori,
                k.nama AS nama_kategori,
                b.satuan,
                b.harga_jual,
                b.stok,
                b.stok_minimum,
                b.status,
                b.created_at,
                b.updated_at
            FROM {$this->table} b
            INNER JOIN kategori k ON k.id = b.id_kategori
            ORDER BY b.nama ASC
            LIMIT 200
        ";

        return $this->fetchAll($sql);
    }

    public function getActive(): array
    {
        // Dipakai restock dan transaksi
        $sql = "
            SELECT 
                b.id,
                b.kode_barang,
                b.barcode,
                b.nama,
                b.id_kategori,
                k.nama AS nama_kategori,
                b.satuan,
                b.harga_jual,
                b.stok,
                b.stok_minimum
            FROM {$this->table} b
            INNER JOIN kategori k ON k.id = b.id_kategori
            WHERE b.status = 'aktif'
            ORDER BY b.nama ASC
            LIMIT 200
        ";

        return $this->fetchAll($sql);
    }

    public function findById(int $id)
    {
        // Detail barang
        $sql = "
            SELECT 
                b.id,
                b.kode_barang,
                b.barcode,
                b.nama,
                b.id_kategori,
                k.nama AS nama_kategori,
                b.satuan,
                b.harga_jual,
                b.stok,
                b.stok_minimum,
                b.status,
                b.created_at,
                b.updated_at
            FROM {$this->table} b
            INNER JOIN kategori k ON k.id = b.id_kategori
            WHERE b.id = :id
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'id' => $id,
        ]);
    }

    public function findActiveById(int $id)
    {
        // Detail barang aktif
        $sql = "
            SELECT 
                b.id,
                b.kode_barang,
                b.barcode,
                b.nama,
                b.id_kategori,
                k.nama AS nama_kategori,
                b.satuan,
                b.harga_jual,
                b.stok,
                b.stok_minimum,
                b.status
            FROM {$this->table} b
            INNER JOIN kategori k ON k.id = b.id_kategori
            WHERE b.id = :id
              AND b.status = 'aktif'
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'id' => $id,
        ]);
    }

    public function create(array $data): bool
    {
        // Simpan barang baru
        $sql = "
            INSERT INTO {$this->table}
                (
                    kode_barang,
                    barcode,
                    nama,
                    id_kategori,
                    satuan,
                    harga_jual,
                    stok,
                    stok_minimum,
                    status
                )
            VALUES
                (
                    :kode_barang,
                    :barcode,
                    :nama,
                    :id_kategori,
                    :satuan,
                    :harga_jual,
                    0,
                    :stok_minimum,
                    :status
                )
        ";

        return $this->execute($sql, [
            'kode_barang' => trim((string) $data['kode_barang']),
            'barcode' => $this->nullable($data['barcode'] ?? ''),
            'nama' => trim((string) $data['nama']),
            'id_kategori' => (int) $data['id_kategori'],
            'satuan' => trim((string) ($data['satuan'] ?? 'pcs')),
            'harga_jual' => (float) $data['harga_jual'],
            'stok_minimum' => (int) $data['stok_minimum'],
            'status' => $data['status'] ?? 'aktif',
        ]);
    }

    public function update(int $id, array $data): bool
    {
        // Update master barang, stok jangan disentuh
        $sql = "
            UPDATE {$this->table}
            SET 
                kode_barang = :kode_barang,
                barcode = :barcode,
                nama = :nama,
                id_kategori = :id_kategori,
                satuan = :satuan,
                harga_jual = :harga_jual,
                stok_minimum = :stok_minimum,
                status = :status
            WHERE id = :id
            LIMIT 1
        ";

        return $this->execute($sql, [
            'kode_barang' => trim((string) $data['kode_barang']),
            'barcode' => $this->nullable($data['barcode'] ?? ''),
            'nama' => trim((string) $data['nama']),
            'id_kategori' => (int) $data['id_kategori'],
            'satuan' => trim((string) ($data['satuan'] ?? 'pcs')),
            'harga_jual' => (float) $data['harga_jual'],
            'stok_minimum' => (int) $data['stok_minimum'],
            'status' => $data['status'] ?? 'aktif',
            'id' => $id,
        ]);
    }

    public function updateHargaJual(int $id, float $hargaJual): bool
    {
        // Dipakai saat restock kalau harga jual baru diisi
        $sql = "
            UPDATE {$this->table}
            SET harga_jual = :harga_jual
            WHERE id = :id
            LIMIT 1
        ";

        return $this->execute($sql, [
            'harga_jual' => $hargaJual,
            'id' => $id,
        ]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        // Update status
        if (!in_array($status, ['aktif', 'nonaktif'], true)) {
            return false;
        }

        $sql = "
            UPDATE {$this->table}
            SET status = :status
            WHERE id = :id
            LIMIT 1
        ";

        return $this->execute($sql, [
            'status' => $status,
            'id' => $id,
        ]);
    }

    public function deleteOrDeactivate(int $id): bool
    {
        // Kalau sudah punya histori, jangan hapus permanen
        if ($this->hasHistory($id)) {
            return $this->updateStatus($id, 'nonaktif');
        }

        $sql = "
            DELETE FROM {$this->table}
            WHERE id = :id
            LIMIT 1
        ";

        return $this->execute($sql, [
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->deleteOrDeactivate($id);
    }

    public function increaseStock(int $id, int $qty): bool
    {
        // Stok masuk dari restock
        if ($qty <= 0) {
            return false;
        }

        $sql = "
            UPDATE {$this->table}
            SET stok = stok + :qty
            WHERE id = :id
            LIMIT 1
        ";

        $statement = $this->query($sql, [
            'qty' => $qty,
            'id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    public function decreaseStock(int $id, int $qty): bool
    {
        // Stok keluar dari transaksi
        if ($qty <= 0) {
            return false;
        }

        $sql = "
            UPDATE {$this->table}
            SET stok = stok - :qty
            WHERE id = :id
              AND stok >= :qty_check
            LIMIT 1
        ";

        $statement = $this->query($sql, [
            'qty' => $qty,
            'qty_check' => $qty,
            'id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    public function kodeExists(string $kodeBarang, ?int $exceptId = null): bool
    {
        return $this->existsByField('kode_barang', $kodeBarang, $exceptId);
    }

    public function barcodeExists(string $barcode, ?int $exceptId = null): bool
    {
        $barcode = trim($barcode);

        if ($barcode === '') {
            return false;
        }

        return $this->existsByField('barcode', $barcode, $exceptId);
    }

    /**
     * Generate barcode internal sequential.
     * Format: KPS + 7 digit angka, contoh: KPS0000001
     * Return: string barcode unik (pasti gak duplikat).
     */
    public function generateNextBarcode(string $prefix = 'KPS', int $padLength = 7): string
    {
        $prefix = trim($prefix) === '' ? 'KPS' : strtoupper(trim($prefix));
        $padLength = max(4, min(12, $padLength));

        // Cari sequence terbesar dari barcode existing yang punya prefix sama
        $sql = "
            SELECT barcode
            FROM {$this->table}
            WHERE barcode LIKE :prefix
            ORDER BY LENGTH(barcode) DESC, barcode DESC
            LIMIT 1
        ";

        $row = $this->fetch($sql, [
            'prefix' => $prefix . '%',
        ]);

        $next = 1;

        if ($row && isset($row['barcode'])) {
            $numericPart = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', (string) $row['barcode']);
            $numericPart = (int) preg_replace('/\D/', '', $numericPart);

            if ($numericPart > 0) {
                $next = $numericPart + 1;
            }
        }

        // Loop maksimal 10x buat handle race condition kalau ada bentrok
        $maxAttempt = 10;

        for ($i = 0; $i < $maxAttempt; $i++) {
            $candidate = $prefix . str_pad((string) $next, $padLength, '0', STR_PAD_LEFT);

            if (!$this->barcodeExists($candidate)) {
                return $candidate;
            }

            $next++;
        }

        // Fallback ekstrim: prefix + microtime
        return $prefix . str_pad((string) (time() % 9999999), $padLength, '0', STR_PAD_LEFT);
    }

    /**
     * Ambil barang by IDs (dipakai cetak label bulk).
     * @param int[] $ids
     */
    public function findManyByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($id) => $id > 0)));

        if (empty($ids)) {
            return [];
        }

        // Bikin placeholder dinamis (?, ?, ?...)
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "
            SELECT 
                b.id,
                b.kode_barang,
                b.barcode,
                b.nama,
                b.satuan,
                b.harga_jual,
                b.stok,
                b.status
            FROM {$this->table} b
            WHERE b.id IN ({$placeholders})
            ORDER BY b.nama ASC
        ";

        // Pake fetchAll dengan positional binding
        $statement = $this->query($sql, $ids);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function hasHistory(int $id): bool
    {
        // Cek histori restock
        $restockSql = "
            SELECT id
            FROM restock
            WHERE id_barang = :id
            LIMIT 1
        ";

        if ($this->fetch($restockSql, ['id' => $id]) !== false) {
            return true;
        }

        // Cek histori transaksi
        $transaksiSql = "
            SELECT id
            FROM detail_transaksi
            WHERE id_barang = :id
            LIMIT 1
        ";

        return $this->fetch($transaksiSql, ['id' => $id]) !== false;
    }

    public function hasTransactions(int $id): bool
    {
        return $this->hasHistory($id);
    }

    public function summary(): array
    {
        // Ringkasan barang
        $sql = "
            SELECT
                COUNT(id) AS total_barang,
                SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) AS barang_aktif,
                SUM(CASE WHEN status = 'nonaktif' THEN 1 ELSE 0 END) AS barang_nonaktif,
                SUM(CASE WHEN status = 'aktif' AND stok <= stok_minimum THEN 1 ELSE 0 END) AS stok_menipis
            FROM {$this->table}
        ";

        $row = $this->fetch($sql);

        return [
            'total_barang' => (int) ($row['total_barang'] ?? 0),
            'barang_aktif' => (int) ($row['barang_aktif'] ?? 0),
            'barang_nonaktif' => (int) ($row['barang_nonaktif'] ?? 0),
            'stok_menipis' => (int) ($row['stok_menipis'] ?? 0),
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
                b.id,
                b.kode_barang,
                b.barcode,
                b.nama,
                b.id_kategori,
                k.nama AS nama_kategori,
                b.satuan,
                b.harga_jual,
                b.stok,
                b.stok_minimum,
                b.status,
                b.created_at,
                b.updated_at
            FROM {$this->table} b
            INNER JOIN kategori k ON k.id = b.id_kategori
            ORDER BY b.nama ASC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        return $this->fetchAll($sql);
    }

    private function existsByField(string $field, string $value, ?int $exceptId = null): bool
    {
        // Field dibatasi biar aman
        $allowedFields = ['kode_barang', 'barcode'];

        if (!in_array($field, $allowedFields, true)) {
            return false;
        }

        $sql = "
            SELECT id
            FROM {$this->table}
            WHERE {$field} = :value
        ";

        $params = [
            'value' => trim($value),
        ];

        if ($exceptId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $exceptId;
        }

        $sql .= " LIMIT 1";

        return $this->fetch($sql, $params) !== false;
    }

    private function nullable($value)
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}