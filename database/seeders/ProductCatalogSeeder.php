<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categoryNames = ['Minuman', 'Mochi Daifuku', 'Mochi Basic'];
        $categoryIds = [];

        foreach ($categoryNames as $name) {
            $id = DB::table('ms_kategori')->where('nama_kategori', $name)->value('id_kategori');

            if ($id === null) {
                $id = (int) DB::table('ms_kategori')->max('id_kategori') + 1;
                DB::table('ms_kategori')->insert([
                    'id_kategori' => $id,
                    'nama_kategori' => $name,
                ]);
            }

            $categoryIds[$name] = (int) $id;
        }

        $products = [
            [
                'nama_produk' => 'Mochi Strawberry Choco',
                'id_kategori' => $categoryIds['Mochi Daifuku'],
                'harga' => 8000,
                'stok' => 10,
                'image' => null,
            ],
            [
                'nama_produk' => 'Mochi Kacang Wijen',
                'id_kategori' => $categoryIds['Mochi Basic'],
                'harga' => 5000,
                'stok' => 20,
                'image' => 'uoncerxYKClLLk7rckUxtDmDLa94kurwDRTye7UT.jpg',
            ],
            [
                'nama_produk' => 'Mochi Nutella',
                'id_kategori' => $categoryIds['Mochi Basic'],
                'harga' => 6000,
                'stok' => 10,
                'image' => 'DeKPt749hm7YKI1eaAaqYkSXyaFz73bZ5ZrvTk5U.jpg',
            ],
            [
                'nama_produk' => 'Mochi Lotus Biscoff',
                'id_kategori' => $categoryIds['Mochi Basic'],
                'harga' => 6000,
                'stok' => 0,
                'image' => 'u8THNqVRW4lmqaXwzocfnbsR4kf8UYOxqK3HMztu.jpg',
            ],
            [
                'nama_produk' => 'Mochi Milo',
                'id_kategori' => $categoryIds['Mochi Basic'],
                'harga' => 5000,
                'stok' => 20,
                'image' => 'N3pcQuWOfuRgoRDhxrpxupDvUGPhaoNsS79HT0KK.jpg',
            ],
        ];

        foreach ($products as $product) {
            $imagePath = null;
            if ($product['image'] !== null) {
                $sourcePath = database_path('seeders/assets/products/'.$product['image']);
                $contents = is_file($sourcePath) ? file_get_contents($sourcePath) : false;

                if ($contents === false || ! Storage::disk('public')->put('products/'.$product['image'], $contents)) {
                    throw new RuntimeException('Gambar produk gagal disalin: '.$product['image']);
                }

                $imagePath = 'products/'.$product['image'];
            }

            $id = DB::table('ms_produk')
                ->where('nama_produk', $product['nama_produk'])
                ->value('id_produk');

            if ($id === null) {
                $id = (int) DB::table('ms_produk')->max('id_produk') + 1;
            }

            DB::table('ms_produk')->updateOrInsert(
                ['id_produk' => $id],
                [
                    'nama_produk' => $product['nama_produk'],
                    'id_kategori' => $product['id_kategori'],
                    'harga' => $product['harga'],
                    'stok' => $product['stok'],
                    'gambar' => $imagePath,
                ],
            );
        }
    }
}
