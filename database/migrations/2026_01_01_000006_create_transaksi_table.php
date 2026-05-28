<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('kode_transaksi', 30)->unique();
            $table->unsignedInteger('id_user');
            $table->dateTime('tanggal');
            $table->decimal('total_jual', 14, 2)->default(0);
            $table->decimal('total_beli', 14, 2)->default(0);
            $table->decimal('total_laba', 14, 2)->default(0);
            $table->enum('metode_bayar', ['cash', 'transfer', 'qris', 'ewallet'])->index('idx_transaksi_metode');
            $table->decimal('nominal_bayar', 14, 2)->default(0);
            $table->decimal('kembalian', 14, 2)->default(0);
            $table->enum('status', ['selesai', 'diubah', 'dibatalkan'])->default('selesai')->index('idx_transaksi_status');
            $table->text('alasan_batal')->nullable();
            $table->dateTime('edited_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('tanggal', 'idx_transaksi_tanggal');
            $table->index('id_user', 'idx_transaksi_user');

            $table->foreign('id_user', 'fk_transaksi_user')
                ->references('id')->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
