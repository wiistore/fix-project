<?php

declare(strict_types=1);

class User extends Model
{
    private $table = 'users';

    public function findByUsername(string $username)
    {
        // Ambil data login
        $sql = "
            SELECT 
                id,
                username,
                username AS nama,
                email,
                password,
                role,
                is_protected,
                status
            FROM {$this->table}
            WHERE username = :login_username OR email = :login_email
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'login_username' => $username,
            'login_email' => $username,
        ]);
    }

    public function findById(int $id)
    {
        // Ambil data user
        $sql = "
            SELECT 
                id,
                username,
                username AS nama,
                email,
                role,
                is_protected,
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

    public function findByIdWithPassword(int $id)
    {
        // Ambil data buat cek password lama
        $sql = "
            SELECT 
                id,
                username,
                email,
                password,
                role,
                is_protected,
                status
            FROM {$this->table}
            WHERE id = :id
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'id' => $id,
        ]);
    }

    public function getAll(): array
    {
        // Ambil kolom yang dipakai aja
        $sql = "
            SELECT 
                id,
                username,
                username AS nama,
                email,
                role,
                is_protected,
                status,
                created_at,
                updated_at
            FROM {$this->table}
            ORDER BY 
                role = 'admin' DESC,
                id ASC
            LIMIT 100
        ";

        return $this->fetchAll($sql);
    }

    public function createKasir(array $data): int
    {
        // User dari sistem selalu kasir
        $sql = "
            INSERT INTO {$this->table}
                (username, email, password, role, is_protected, status)
            VALUES
                (:username, :email, :password, 'kasir', 0, :status)
        ";

        $this->execute($sql, [
            'username' => trim((string) $data['username']),
            'email' => trim((string) $data['email']),
            'password' => Security::passwordHash((string) $data['password']),
            'status' => $data['status'] ?? 'aktif',
        ]);

        return $this->lastInsertId();
    }

    public function updateKasir(int $id, array $data): bool
    {
        $user = $this->findById($id);

        if (!$user || $user['role'] !== 'kasir') {
            return false;
        }

        // Admin cuma boleh edit kasir
        $sql = "
            UPDATE {$this->table}
            SET 
                username = :username,
                email = :email,
                status = :status
            WHERE id = :id
              AND role = 'kasir'
              AND is_protected = 0
            LIMIT 1
        ";

        $this->execute($sql, [
            'username' => trim((string) $data['username']),
            'email' => trim((string) $data['email']),
            'status' => $data['status'] ?? 'aktif',
            'id' => $id,
        ]);

        return true;
    }

    public function resetPassword(int $id, string $password): bool
    {
        $user = $this->findById($id);

        if (!$user || $user['role'] !== 'kasir') {
            return false;
        }

        // Reset password hanya untuk kasir
        $sql = "
            UPDATE {$this->table}
            SET password = :password
            WHERE id = :id
              AND role = 'kasir'
              AND is_protected = 0
            LIMIT 1
        ";

        $this->execute($sql, [
            'password' => Security::passwordHash($password),
            'id' => $id,
        ]);

        return true;
    }

    public function updateOwnPassword(int $id, string $password): bool
    {
        $user = $this->findById($id);

        if (!$user) {
            return false;
        }

        // User aktif ubah password sendiri
        $sql = "
            UPDATE {$this->table}
            SET password = :password
            WHERE id = :id
            LIMIT 1
        ";

        $this->execute($sql, [
            'password' => Security::passwordHash($password),
            'id' => $id,
        ]);

        return true;
    }

    public function deleteOrDeactivate(int $id): bool
    {
        $user = $this->findById($id);

        if (!$user) {
            return false;
        }

        if ($user['role'] === 'admin' || (int) $user['is_protected'] === 1) {
            return false;
        }

        // Amanin histori, jadi kasir dinonaktifkan aja
        $sql = "
            UPDATE {$this->table}
            SET status = 'nonaktif'
            WHERE id = :id
              AND role = 'kasir'
              AND is_protected = 0
            LIMIT 1
        ";

        $this->execute($sql, [
            'id' => $id,
        ]);

        return true;
    }

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $sql = "
            SELECT id
            FROM {$this->table}
            WHERE username = :username
        ";

        $params = [
            'username' => trim($username),
        ];

        if ($exceptId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $exceptId;
        }

        $sql .= " LIMIT 1";

        return $this->fetch($sql, $params) !== false;
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $email = trim($email);

        if ($email === '') {
            return false;
        }

        $sql = "
            SELECT id
            FROM {$this->table}
            WHERE email = :email
        ";

        $params = [
            'email' => $email,
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
                username,
                username AS nama,
                email,
                role,
                is_protected,
                status,
                created_at,
                updated_at
            FROM {$this->table}
            ORDER BY 
                role = 'admin' DESC,
                id ASC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        return $this->fetchAll($sql);
    }

    public function hasTransactions(int $id): bool
    {
        $sql = "
            SELECT id
            FROM transaksi
            WHERE id_user = :id
            LIMIT 1
        ";

        return $this->fetch($sql, ['id' => $id]) !== false;
    }
}