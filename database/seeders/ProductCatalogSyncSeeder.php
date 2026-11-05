<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProductCatalogSyncSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['nama_produk' => 'Mochi Strawberry Choco', 'nama_kategori' => 'Mochi Daifuku', 'harga' => 8000, 'stok' => 9, 'gambar' => 'products/BomD4sCVx62qMx1Voe5BENUkr8dcl0y8CZXulfgN.png'],
            ['nama_produk' => 'Mochi Kacang Wijen', 'nama_kategori' => 'Mochi Basic', 'harga' => 5000, 'stok' => 19, 'gambar' => 'products/uoncerxYKClLLk7rckUxtDmDLa94kurwDRTye7UT.jpg'],
            ['nama_produk' => 'Mochi Nutella', 'nama_kategori' => 'Mochi Basic', 'harga' => 6000, 'stok' => 9, 'gambar' => 'products/DeKPt749hm7YKI1eaAaqYkSXyaFz73bZ5ZrvTk5U.jpg'],
            ['nama_produk' => 'Mochi Lotus Biscoff', 'nama_kategori' => 'Mochi Basic', 'harga' => 6000, 'stok' => 0, 'gambar' => 'products/u8THNqVRW4lmqaXwzocfnbsR4kf8UYOxqK3HMztu.jpg'],
            ['nama_produk' => 'Mochi Milo', 'nama_kategori' => 'Mochi Basic', 'harga' => 5000, 'stok' => 18, 'gambar' => 'products/N3pcQuWOfuRgoRDhxrpxupDvUGPhaoNsS79HT0KK.jpg'],
            ['nama_produk' => 'Mochi Choco Crunchy', 'nama_kategori' => 'Mochi Basic', 'harga' => 4000, 'stok' => 0, 'gambar' => 'products/0cPLniKsRt1ijzZpT8heyZ1Ok4pTUssepQz2wmcF.jpg'],
            ['nama_produk' => 'Mochi Nanas', 'nama_kategori' => 'Mochi Basic', 'harga' => 4000, 'stok' => 0, 'gambar' => 'products/nUWlJAMtmAD8ab1PtTIGkyzE7pmRabbuwPvk1KNe.jpg'],
            ['nama_produk' => 'Mochi Matcha', 'nama_kategori' => 'Mochi Basic', 'harga' => 5000, 'stok' => 0, 'gambar' => 'products/b6DAnxLc5A0y5FGtNuGvy1CbIbB47Gcj5cVCggPj.jpg'],
            ['nama_produk' => 'Mochi Tiramisu', 'nama_kategori' => 'Mochi Basic', 'harga' => 5000, 'stok' => 0, 'gambar' => 'products/b2afYgpzG8cWfuYOEBSpgOBLm3fQD6HVAQksQ0eF.jpg'],
            ['nama_produk' => 'Mochi Blueberry', 'nama_kategori' => 'Mochi Basic', 'harga' => 4000, 'stok' => 0, 'gambar' => 'products/RL8DG9PtN408HJDOLgQzL0ZRgdorXXn2nORz43sL.jpg'],
            ['nama_produk' => 'Mochi Starwberry', 'nama_kategori' => 'Mochi Basic', 'harga' => 4000, 'stok' => 0, 'gambar' => 'products/TNoQiNghD5cgd97UaYYzzilzfVp1HT95XPQ4GTMV.jpg'],
            ['nama_produk' => 'Mochi Coklat Kacang', 'nama_kategori' => 'Mochi Basic', 'harga' => 4000, 'stok' => 0, 'gambar' => 'products/ALa3aG6BwLGrmWpsFtCkpn77FeQlqefQjdnPTBmT.jpg'],
            ['nama_produk' => 'Mochi Keju', 'nama_kategori' => 'Mochi Basic', 'harga' => 4000, 'stok' => 0, 'gambar' => 'products/We0adbPNJEozrSpqui4pCIhfl6Zt64jgxHJ3Fjk0.png'],
            ['nama_produk' => 'Mochi Coklat', 'nama_kategori' => 'Mochi Basic', 'harga' => 4000, 'stok' => 0, 'gambar' => 'products/nFijdIhjclS2eVN2XdgBJy5kgJdSWGRwKMDX4kYf.png'],
            ['nama_produk' => 'Mochi Kacang', 'nama_kategori' => 'Mochi Basic', 'harga' => 4000, 'stok' => 0, 'gambar' => 'products/LK65EY0K8Vqv2ui0kFnFzyp4bvRcozVlGgQswGX1.png'],
            ['nama_produk' => 'Mochi Oreo', 'nama_kategori' => 'Mochi Basic', 'harga' => 4000, 'stok' => 0, 'gambar' => 'products/uRncV4soLzBOxYuKmFS6GX5SikNRUmLE6kHKfsWe.png'],
        ];

        $addedCount = 0;
        $imageCount = 0;

        DB::transaction(function () use ($products, &$addedCount, &$imageCount): void {
            $categories = collect($products)
                ->pluck('nama_kategori')
                ->unique()
                ->mapWithKeys(function (string $name): array {
                    $id = DB::table('ms_kategori')->where('nama_kategori', $name)->value('id_kategori');

                    if ($id === null) {
                        $id = (int) DB::table('ms_kategori')->max('id_kategori') + 1;
                        if ($id > 4294967295) {
                            throw new RuntimeException('ID kategori telah mencapai batas maksimum database.');
                        }

                        DB::table('ms_kategori')->insert([
                            'id_kategori' => $id,
                            'nama_kategori' => $name,
                        ]);
                    }

                    return [$name => (int) $id];
                });

            $nextId = (int) DB::table('ms_produk')->max('id_produk');

            foreach ($products as $product) {
                $imagePath = $product['gambar'];
                if (! Storage::disk('public')->exists($imagePath)) {
                    throw new RuntimeException('Gambar produk tidak ditemukan: storage/app/public/'.$imagePath);
                }

                $existing = DB::table('ms_produk')
                    ->where('nama_produk', $product['nama_produk'])
                    ->first();

                if ($existing !== null) {
                    if (blank($existing->gambar)) {
                        DB::table('ms_produk')
                            ->where('id_produk', $existing->id_produk)
                            ->update(['gambar' => $imagePath]);
                        $imageCount++;
                    }

                    continue;
                }

                $nextId++;
                if ($nextId > 4294967295) {
                    throw new RuntimeException('ID produk telah mencapai batas maksimum database.');
                }

                DB::table('ms_produk')->insert([
                    'id_produk' => $nextId,
                    'id_kategori' => $categories[$product['nama_kategori']],
                    'nama_produk' => $product['nama_produk'],
                    'harga' => $product['harga'],
                    'stok' => $product['stok'],
                    'gambar' => $imagePath,
                ]);
                $addedCount++;
            }
        });

        $this->command?->info("Sinkronisasi katalog selesai: {$addedCount} produk ditambahkan, {$imageCount} gambar dilengkapi.");
    }
}
