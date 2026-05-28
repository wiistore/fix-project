<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    protected $table = 'transaksi';

    public $timestamps = false;

    protected $fillable = [
        'kode_transaksi',
        'id_user',
        'tanggal',
        'total_jual',
        'total_beli',
        'total_laba',
        'metode_bayar',
        'nominal_bayar',
        'kembalian',
        'status',
        'alasan_batal',
        'edited_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'datetime',
            'total_jual' => 'decimal:2',
            'total_beli' => 'decimal:2',
            'total_laba' => 'decimal:2',
            'nominal_bayar' => 'decimal:2',
            'kembalian' => 'decimal:2',
            'edited_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /* ----------------- Relations ----------------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function detailTransaksi(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'id_transaksi');
    }

    public function items(): HasMany
    {
        return $this->detailTransaksi();
    }

    /* ----------------- Helpers ----------------- */

    public function isDibatalkan(): bool
    {
        return $this->status === 'dibatalkan';
    }

    public function isDiubah(): bool
    {
        return $this->status === 'diubah';
    }

    public static function generateKode(): string
    {
        do {
            $kode = 'TRX'.date('YmdHis').random_int(100, 999);
        } while (static::where('kode_transaksi', $kode)->exists());

        return $kode;
    }

    public static function isValidPaymentMethod(string $method): bool
    {
        return in_array($method, ['cash', 'transfer', 'qris', 'ewallet'], true);
    }
}
