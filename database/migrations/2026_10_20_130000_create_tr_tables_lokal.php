<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tr_pesanan', function (Blueprint $table) {
            $table->unsignedInteger('id_pesanan')->primary();
            $table->dateTime('tanggal');
            $table->unsignedInteger('total');
            $table->string('status', 12);
            $table->string('ms_user_id_user', 20)->nullable();
        });

        Schema::create('tr_detail_pesanan', function (Blueprint $table) {
            $table->unsignedInteger('id_detail')->primary();
            $table->string('id_pesanan', 20);
            $table->string('id_produk', 20);
            $table->unsignedInteger('jumlah');
            $table->unsignedInteger('harga');
            $table->unsignedInteger('sub_total');
        });

        Schema::create('tr_pembayaran', function (Blueprint $table) {
            $table->unsignedInteger('id_pembayaran')->primary();
            $table->string('id_pesanan', 20);
            $table->string('metode', 20);
            $table->unsignedInteger('jumlah');
            $table->string('status_pembayaran', 12);
            $table->dateTime('waktu_bayar')->nullable();
        });

        Schema::create('tr_stok', function (Blueprint $table) {
            $table->unsignedInteger('id_stok')->primary();
            $table->string('id_produk', 20);
            $table->unsignedInteger('id_kategori')->nullable();
            $table->date('tanggal');
            $table->integer('jumlah');
            $table->string('keterangan', 100)->nullable();
            $table->string('ms_user_id_user', 20)->nullable();
            $table->string('ms_produk_id_produk', 20);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_stok');
        Schema::dropIfExists('tr_pembayaran');
        Schema::dropIfExists('tr_detail_pesanan');
        Schema::dropIfExists('tr_pesanan');
    }
};