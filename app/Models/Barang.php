<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barang extends Model
{
    protected $table = 'barang';

    protected $fillable = [
        'kode_barang',
        'nama',
        'barcode',
        'id_kategori',
        'satuan',
        'harga_jual',
        'stok',
        'stok_minimum',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'harga_jual' => 'decimal:2',
            'stok' => 'integer',
            'stok_minimum' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'id_kategori');
    }

    public function restocks(): HasMany
    {
        return $this->hasMany(Restock::class, 'id_barang');
    }

    public function detailTransaksi(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'id_barang');
    }

    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }

    public function isStokMenipis(): bool
    {
        return $this->stok <= $this->stok_minimum;
    }

    public function isStokHabis(): bool
    {
        return $this->stok <= 0;
    }
}
