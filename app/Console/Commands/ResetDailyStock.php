<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResetDailyStock extends Command
{
    protected $signature = 'stok:reset-harian';

    protected $description = 'Reset stok semua produk ke nol dan catat perubahan stok';

    public function handle(): int
    {
        $resetCount = DB::transaction(function (): int {
            $products = Product::query()
                ->orderBy('id_produk')
                ->lockForUpdate()
                ->get();
            $nextStockId = (int) DB::table('tr_stok')->lockForUpdate()->max('id_stok') + 1;
            $now = now('Asia/Jakarta');
            $resetCount = 0;

            foreach ($products as $product) {
                $currentStock = (int) $product->stok;

                if ($currentStock === 0) {
                    if ($product->stok !== 0) {
                        $product->stok = 0;
                        $product->save();
                    }

                    continue;
                }

                if ($currentStock > 0) {
                    if ($nextStockId > 2147483647) {
                        throw new RuntimeException('ID pencatatan stok telah mencapai batas maksimum database.');
                    }

                    DB::table('tr_stok')->insert([
                        'id_stok' => $nextStockId++,
                        'id_produk' => (string) $product->id_produk,
                        'id_kategori' => $product->id_kategori,
                        'tanggal' => $now->toDateString(),
                        'jumlah' => -$currentStock,
                        'keterangan' => 'Reset stok harian',
                        'ms_user_id_user' => null,
                        'ms_produk_id_produk' => (string) $product->id_produk,
                    ]);
                    $resetCount++;
                }

                $product->stok = 0;
                $product->save();
            }

            return $resetCount;
        });

        $this->info("Stok harian berhasil direset untuk {$resetCount} produk.");

        return self::SUCCESS;
    }
}
