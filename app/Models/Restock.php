<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Restock extends Model
{
    protected $table = 'restock';

    public $timestamps = false;

    protected $fillable = [
        'tanggal',
        'tipe',
        'id_barang',
        'id_supplier',
        'id_user',
        'qty',
        'harga_beli',
        'harga_jual_baru',
        'total_nilai',
        'catatan',
        'alasan',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'qty' => 'integer',
            'harga_beli' => 'decimal:2',
            'harga_jual_baru' => 'decimal:2',
            'total_nilai' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
