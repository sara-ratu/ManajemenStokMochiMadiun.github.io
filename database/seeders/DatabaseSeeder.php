<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ms_user')->insert([
            [
                'nama_lengkap' => 'Mochi Madiun',
                'email'        => 'admin@mochi.test',
                'password'     => Hash::make('secret-passsword'),
                'status'       => 'Aktif',
                'role'         => 'SuperAdmin',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'nama_lengkap' => 'Kasir',
                'email'        => 'kasir@example.com',
                'password'     => Hash::make('secret-password'),
                'status'       => 'Aktif',
                'role'         => 'Kasir',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);

        DB::table('ms_kategori')->insert([
            ['id_kategori' => 1, 'nama_kategori' => 'Mochi'],
            ['id_kategori' => 2, 'nama_kategori' => 'Minuman'],
            ['id_kategori' => 3, 'nama_kategori' => 'Frozen'],
        ]);

        DB::table('ms_produk')->insert([
            ['id_produk' => 1, 'nama_produk' => 'Mochi Kacang',     'id_kategori' => 1, 'harga' => 8000,  'stok' => 30, 'gambar' => null],
            ['id_produk' => 2, 'nama_produk' => 'Mochi Coklat',     'id_kategori' => 1, 'harga' => 9000,  'stok' => 25, 'gambar' => null],
            ['id_produk' => 3, 'nama_produk' => 'Mochi Strawberry', 'id_kategori' => 1, 'harga' => 9000,  'stok' => 20, 'gambar' => null],
            ['id_produk' => 4, 'nama_produk' => 'Es Teh Manis',     'id_kategori' => 2, 'harga' => 5000,  'stok' => 40, 'gambar' => null],
            ['id_produk' => 5, 'nama_produk' => 'Matcha Latte',     'id_kategori' => 2, 'harga' => 15000, 'stok' => 15, 'gambar' => null],
            ['id_produk' => 6, 'nama_produk' => 'Mochi Ice Vanila', 'id_kategori' => 3, 'harga' => 12000, 'stok' => 18, 'gambar' => null],
            ['id_produk' => 7, 'nama_produk' => 'Mochi Ice Coklat', 'id_kategori' => 3, 'harga' => 12000, 'stok' => 18, 'gambar' => null],
        ]);
    }
}