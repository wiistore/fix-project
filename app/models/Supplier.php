<?php

declare(strict_types=1);

class Supplier extends Model
{
    private $table = 'supplier';

    public function getAll(): array
    {
        // Ambil data utama
        $sql = "
            SELECT 
                s.id,
                s.nama,
                s.kontak_person,
                s.no_hp,
                s.alamat,
                s.keterangan,
                s.status,
                s.created_at,
                s.updated_at,
                COUNT(r.id) AS total_restock
            FROM {$this->table} s
            LEFT JOIN restock r ON r.id_supplier = s.id
            GROUP BY 
                s.id,
                s.nama,
                s.kontak_person,
                s.no_hp,
                s.alamat,
                s.keterangan,
                s.status,
                s.created_at,
                s.updated_at
            ORDER BY s.nama ASC
            LIMIT 100
        ";

        return $this->fetchAll($sql);
    }

    public function getActive(): array
    {
        // Dipakai nanti buat form restock
        $sql = "
            SELECT 
                id,
                nama,
                kontak_person,
                no_hp
            FROM {$this->table}
            WHERE status = 'aktif'
            ORDER BY nama ASC
            LIMIT 100
        ";

        return $this->fetchAll($sql);
    }

    public function findById(int $id)
    {
        // Ambil detail supplier
        $sql = "
            SELECT 
                id,
                nama,
                kontak_person,
                no_hp,
                alamat,
                keterangan,
                status,
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
        // Simpan supplier
        $sql = "
            INSERT INTO {$this->table}
                (nama, kontak_person, no_hp, alamat, keterangan, status)
            VALUES
                (:nama, :kontak_person, :no_hp, :alamat, :keterangan, :status)
        ";

        return $this->execute($sql, [
            'nama' => trim((string) $data['nama']),
            'kontak_person' => $this->nullable($data['kontak_person'] ?? ''),
            'no_hp' => $this->nullable($data['no_hp'] ?? ''),
            'alamat' => $this->nullable($data['alamat'] ?? ''),
            'keterangan' => $this->nullable($data['keterangan'] ?? ''),
            'status' => $data['status'] ?? 'aktif',
        ]);
    }

    public function update(int $id, array $data): bool
    {
        // Update supplier
        $sql = "
            UPDATE {$this->table}
            SET 
                nama = :nama,
                kontak_person = :kontak_person,
                no_hp = :no_hp,
                alamat = :alamat,
                keterangan = :keterangan,
                status = :status
            WHERE id = :id
            LIMIT 1
        ";

        return $this->execute($sql, [
            'nama' => trim((string) $data['nama']),
            'kontak_person' => $this->nullable($data['kontak_person'] ?? ''),
            'no_hp' => $this->nullable($data['no_hp'] ?? ''),
            'alamat' => $this->nullable($data['alamat'] ?? ''),
            'keterangan' => $this->nullable($data['keterangan'] ?? ''),
            'status' => $data['status'] ?? 'aktif',
            'id' => $id,
        ]);
    }

    public function deleteOrDeactivate(int $id): bool
    {
        // Kalau sudah dipakai restock, jangan hapus histori
        if ($this->isUsedByRestock($id)) {
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

    public function isUsedByRestock(int $id): bool
    {
        // Cek relasi ke restock
        $sql = "
            SELECT id
            FROM restock
            WHERE id_supplier = :id
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
                s.id,
                s.nama,
                s.kontak_person,
                s.no_hp,
                s.alamat,
                s.keterangan,
                s.status,
                s.created_at,
                s.updated_at,
                COUNT(r.id) AS total_restock
            FROM {$this->table} s
            LEFT JOIN restock r ON r.id_supplier = s.id
            GROUP BY 
                s.id,
                s.nama,
                s.kontak_person,
                s.no_hp,
                s.alamat,
                s.keterangan,
                s.status,
                s.created_at,
                s.updated_at
            ORDER BY s.nama ASC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        return $this->fetchAll($sql);
    }

    private function nullable($value)
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}