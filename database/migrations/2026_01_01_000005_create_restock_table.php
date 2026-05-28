<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restock', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->date('tanggal');
            $table->enum('tipe', ['masuk', 'keluar'])->default('masuk')->index('idx_restock_tipe');
            $table->unsignedInteger('id_barang');
            $table->unsignedInteger('id_supplier')->nullable();
            $table->unsignedInteger('id_user');
            $table->integer('qty');
            $table->decimal('harga_beli', 12, 2);
            $table->decimal('harga_jual_baru', 12, 2)->nullable();
            $table->decimal('total_nilai', 14, 2);
            $table->text('catatan')->nullable();
            $table->text('alasan')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('tanggal', 'idx_restock_tanggal');
            $table->index('id_supplier', 'idx_restock_supplier');

            $table->foreign('id_barang', 'fk_restock_barang')
                ->references('id')->on('barang')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('id_supplier', 'fk_restock_supplier')
                ->references('id')->on('supplier')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('id_user', 'fk_restock_user')
                ->references('id')->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restock');
    }
};
