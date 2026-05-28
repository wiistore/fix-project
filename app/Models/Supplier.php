<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $table = 'supplier';

    protected $fillable = [
        'nama',
        'kontak_person',
        'no_hp',
        'alamat',
        'keterangan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function restocks(): HasMany
    {
        return $this->hasMany(Restock::class, 'id_supplier');
    }

    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }
}
