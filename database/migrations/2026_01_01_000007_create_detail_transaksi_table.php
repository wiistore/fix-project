<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_transaksi', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->unsignedInteger('id_transaksi');
            $table->unsignedInteger('id_barang');
            $table->integer('qty');
            $table->decimal('harga_jual', 12, 2);
            $table->decimal('harga_beli', 12, 2);
            $table->decimal('subtotal_jual', 14, 2);
            $table->decimal('subtotal_beli', 14, 2);
            $table->decimal('laba_item', 14, 2);

            $table->index('id_barang', 'idx_detail_barang');

            $table->foreign('id_transaksi', 'fk_detail_transaksi')
                ->references('id')->on('transaksi')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_barang', 'fk_detail_barang')
                ->references('id')->on('barang')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_transaksi');
    }
};
