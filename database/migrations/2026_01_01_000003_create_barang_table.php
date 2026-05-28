<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('kode_barang', 50)->unique();
            $table->string('nama', 150);
            $table->string('barcode', 100)->nullable()->unique();
            $table->unsignedInteger('id_kategori');
            $table->string('satuan', 30)->default('pcs');
            $table->decimal('harga_jual', 12, 2)->default(0);
            $table->integer('stok')->default(0)->index('idx_barang_stok');
            $table->integer('stok_minimum')->default(5);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();

            $table->index('id_kategori', 'idx_barang_kategori');
            $table->index('nama', 'idx_barang_nama');

            $table->foreign('id_kategori', 'fk_barang_kategori')
                ->references('id')->on('kategori')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
