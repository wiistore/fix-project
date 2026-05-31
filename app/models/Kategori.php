<?php

declare(strict_types=1);

class Kategori extends Model
{
    private $table = 'kategori';

    public function getAll(): array
    {
        // Ambil data utama
        $sql = "
            SELECT 
                id,
                nama,
                deskripsi,
                created_at,
                updated_at
            FROM {$this->table}
            ORDER BY nama ASC
            LIMIT 100
        ";

        return $this->fetchAll($sql);
    }

    public function findById(int $id)
    {
        // Ambil detail kategori
        $sql = "
            SELECT 
                id,
                nama,
                deskripsi,
                created_at,
                updated_at
            FROM {$this->table}
            WHERE id = :id
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'id' => $id,
        ]);
    }

    public function create(array $data): bool
    {
        // Simpan kategori
        $sql = "
            INSERT INTO {$this->table}
                (nama, deskripsi)
            VALUES
                (:nama, :deskripsi)
        ";

        return $this->execute($sql, [
            'nama' => trim((string) $data['nama']),
            'deskripsi' => trim((string) ($data['deskripsi'] ?? '')),
        ]);
    }

    public function update(int $id, array $data): bool
    {
        // Update kategori
        $sql = "
            UPDATE {$this->table}
            SET 
                nama = :nama,
                deskripsi = :deskripsi
            WHERE id = :id
            LIMIT 1
        ";

        return $this->execute($sql, [
            'nama' => trim((string) $data['nama']),
            'deskripsi' => trim((string) ($data['deskripsi'] ?? '')),
            'id' => $id,
        ]);
    }

    public function deleteOrDeactivate(int $id): bool
    {
        // Tabel kategori belum punya status, jadi hapus permanen kalau aman
        if ($this->isUsedByBarang($id)) {
            return false;
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

    public function isUsedByBarang(int $id): bool
    {
        // Cek relasi ke barang
        $sql = "
            SELECT id
            FROM barang
            WHERE id_kategori = :id
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'id' => $id,
        ]) !== false;
    }

    public function namaExists(string $nama, ?int $exceptId = null): bool
    {
        // Cek nama duplikat
        $sql = "
            SELECT id
            FROM {$this->table}
            WHERE nama = :nama
        ";

        $params = [
            'nama' => trim($nama),
        ];

        if ($exceptId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $exceptId;
        }

        $sql .= " LIMIT 1";

        return $this->fetch($sql, $params) !== false;
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
                id,
                nama,
                deskripsi,
                created_at,
                updated_at
            FROM {$this->table}
            ORDER BY nama ASC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        return $this->fetchAll($sql);
    }
}