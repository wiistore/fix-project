<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'is_protected',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_protected' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /* ----------------- Relations ----------------- */

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_user');
    }

    public function restocks(): HasMany
    {
        return $this->hasMany(Restock::class, 'id_user');
    }

    /* ----------------- Helpers ----------------- */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isKasir(): bool
    {
        return $this->role === 'kasir';
    }

    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }

    /**
     * Akses kompat dengan view native: $user['nama'].
     */
    public function getNamaAttribute(): string
    {
        return $this->username;
    }

    /**
     * Compatibility untuk view yang akses array-style.
     */
    public function offsetExists($offset): bool
    {
        if (in_array($offset, ['nama'], true)) {
            return true;
        }

        return parent::offsetExists($offset);
    }

    public function offsetGet($offset): mixed
    {
        if ($offset === 'nama') {
            return $this->username;
        }

        return parent::offsetGet($offset);
    }
}
