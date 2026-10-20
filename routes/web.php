<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'create'])->name('home');
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::get('/pesan', [CustomerOrderController::class, 'create'])->name('customer-orders.create');
Route::post('/pesan', [CustomerOrderController::class, 'store'])->name('customer-orders.store');
Route::get('/pesanan/{order}', [CustomerOrderController::class, 'track'])
    ->whereNumber('order')
    ->middleware('signed')
    ->name('customer-orders.track');
Route::get('/pesanan/{order}/status', [CustomerOrderController::class, 'trackStatus'])
    ->whereNumber('order')
    ->middleware('signed')
    ->name('customer-orders.status');

Route::middleware('auth')->group(function () {
    Route::middleware('can:manage-products')->group(function () {
        Route::get('/produk', [ProductController::class, 'index'])->name('products.index');
        Route::get('/produk/tambah', [ProductController::class, 'create'])->name('products.create');
        Route::post('/produk', [ProductController::class, 'store'])->name('products.store');
        Route::get('/produk/{product}/edit', [ProductController::class, 'edit'])->whereNumber('product')->name('products.edit');
        Route::put('/produk/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.update');
        Route::delete('/produk/{product}', [ProductController::class, 'destroy'])->whereNumber('product')->name('products.destroy');
    });

    Route::middleware('can:manage-stocks')->group(function () {
        Route::get('/stok', [StockController::class, 'index'])->name('stocks.index');
        Route::post('/stok', [StockController::class, 'store'])->name('stocks.store');
    });

    Route::middleware('can:view-sales')->group(function () {
        Route::get('/transaksi', [SalesController::class, 'index'])->name('sales.index');
        Route::get('/transaksi/{sale}', [SalesController::class, 'show'])->whereNumber('sale')->name('sales.show');
    });
    Route::post('/transaksi', [SalesController::class, 'store'])
        ->middleware('can:cashier')
        ->name('sales.store');

    Route::middleware('can:cashier')->group(function () {
        Route::get('/order-pelanggan', [CustomerOrderController::class, 'cashierIndex'])
            ->name('customer-orders.cashier.index');
        Route::get('/order-pelanggan/notifikasi', [CustomerOrderController::class, 'notifications'])
            ->name('customer-orders.notifications');
        Route::get('/order-pelanggan/{order}', [CustomerOrderController::class, 'cashierShow'])
            ->whereNumber('order')
            ->name('customer-orders.cashier.show');
        Route::post('/order-pelanggan/{order}/batal', [CustomerOrderController::class, 'reject'])
            ->whereNumber('order')
            ->name('customer-orders.cashier.reject');
        Route::post('/order-pelanggan/{order}/selesaikan', [CustomerOrderController::class, 'complete'])
            ->whereNumber('order')
            ->name('customer-orders.cashier.complete');
    });

    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
});
