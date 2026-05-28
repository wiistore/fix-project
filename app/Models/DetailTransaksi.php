<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailTransaksi extends Model
{
    protected $table = 'detail_transaksi';

    public $timestamps = false;

    protected $fillable = [
        'id_transaksi',
        'id_barang',
        'qty',
        'harga_jual',
        'harga_beli',
        'subtotal_jual',
        'subtotal_beli',
        'laba_item',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_jual' => 'decimal:2',
            'harga_beli' => 'decimal:2',
            'subtotal_jual' => 'decimal:2',
            'subtotal_beli' => 'decimal:2',
            'laba_item' => 'decimal:2',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
