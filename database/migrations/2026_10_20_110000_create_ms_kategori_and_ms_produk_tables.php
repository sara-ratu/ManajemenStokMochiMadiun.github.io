<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ms_kategori', function (Blueprint $table) {
            $table->unsignedInteger('id_kategori')->primary();
            $table->string('nama_kategori', 50);
        });

        Schema::create('ms_produk', function (Blueprint $table) {
            $table->unsignedInteger('id_produk')->primary();
            $table->string('nama_produk', 30);
            $table->unsignedInteger('id_kategori')->nullable();
            $table->unsignedInteger('harga');
            $table->integer('stok')->default(0);
            $table->string('gambar', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_produk');
        Schema::dropIfExists('ms_kategori');
    }
};