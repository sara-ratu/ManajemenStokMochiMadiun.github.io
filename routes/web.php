<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'create'])->name('home');
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/produk', [ProductController::class, 'index'])->name('products.index');
    Route::get('/stok', [StockController::class, 'index'])->name('stocks.index');
    Route::post('/stok', [StockController::class, 'store'])
        ->middleware('can:manage-stocks')
        ->name('stocks.store');
    Route::get('/transaksi', [SalesController::class, 'index'])->name('sales.index');
    Route::post('/transaksi', [SalesController::class, 'store'])->name('sales.store');
    Route::get('/transaksi/{sale}', [SalesController::class, 'show'])->whereNumber('sale')->name('sales.show');
    Route::middleware('can:manage-products')->group(function () {
        Route::get('/produk/tambah', [ProductController::class, 'create'])->name('products.create');
        Route::post('/produk', [ProductController::class, 'store'])->name('products.store');
        Route::get('/produk/{product}/edit', [ProductController::class, 'edit'])->whereNumber('product')->name('products.edit');
        Route::put('/produk/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.update');
        Route::delete('/produk/{product}', [ProductController::class, 'destroy'])->whereNumber('product')->name('products.destroy');
    });

    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
});
