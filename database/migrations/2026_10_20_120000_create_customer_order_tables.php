<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tr_customer_orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('tracking_token')->unique();
            $table->string('nama_pelanggan', 60);
            $table->string('no_telepon', 20)->nullable();
            $table->unsignedInteger('total');
            $table->string('status', 20)->default('Menunggu');
            $table->unsignedInteger('sale_id')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('tr_customer_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_order_id')->constrained('tr_customer_orders')->cascadeOnDelete();
            $table->unsignedInteger('id_produk');
            $table->string('nama_produk', 60);
            $table->unsignedInteger('jumlah');
            $table->unsignedInteger('harga');
            $table->unsignedInteger('sub_total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_customer_order_items');
        Schema::dropIfExists('tr_customer_orders');
    }
};
