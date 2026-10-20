<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SaleProcessor
{
    /**
     * @param  array<int, array{id_produk: int|string, jumlah: int|string, harga?: int|string}>  $items
     */
    public function complete(array $items, string $method, int $amountPaid, int|string $cashierId): int
    {
        return DB::transaction(function () use ($items, $method, $amountPaid, $cashierId): int {
            $itemIds = collect($items)
                ->pluck('id_produk')
                ->map(fn ($id): int => (int) $id)
                ->sort()
                ->values();
            $products = Product::query()
                ->whereIn('id_produk', $itemIds)
                ->orderBy('id_produk')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (Product $product): int => (int) $product->id_produk);

            if ($products->count() !== $itemIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Satu atau lebih produk sudah tidak tersedia.',
                ]);
            }

            $lines = [];
            $total = 0;
            foreach ($items as $item) {
                $product = $products->get((int) $item['id_produk']);
                $quantity = (int) $item['jumlah'];
                $availableStock = max((int) $product->stok, 0);

                if ($availableStock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Stok '.$product->nama_produk.' tidak mencukupi. Tersedia '.$availableStock.' pcs.',
                    ]);
                }

                $price = (int) ($item['harga'] ?? $product->harga);
                if ($price < 0 || $price > intdiv(2147483647, $quantity)) {
                    throw ValidationException::withMessages([
                        'items' => 'Harga atau jumlah '.$product->nama_produk.' melebihi batas transaksi.',
                    ]);
                }

                $subTotal = $price * $quantity;
                if ($total > 2147483647 - $subTotal) {
                    throw ValidationException::withMessages([
                        'items' => 'Total transaksi melebihi batas yang dapat disimpan.',
                    ]);
                }

                $total += $subTotal;
                $lines[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => $price,
                    'sub_total' => $subTotal,
                ];
            }

            if ($amountPaid < $total) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Pembayaran kurang dari total belanja.',
                ]);
            }

            $saleId = (int) DB::table('tr_pesanan')->lockForUpdate()->max('id_pesanan') + 1;
            $paymentId = (int) DB::table('tr_pembayaran')->lockForUpdate()->max('id_pembayaran') + 1;
            $detailId = (int) DB::table('tr_detail_pesanan')->lockForUpdate()->max('id_detail') + 1;
            if (
                max($saleId, $paymentId, $detailId) > 2147483647
                || $detailId > 2147483647 - count($lines) + 1
            ) {
                throw new RuntimeException('Nomor transaksi telah mencapai batas maksimum database.');
            }

            $now = now('Asia/Jakarta');
            DB::table('tr_pesanan')->insert([
                'id_pesanan' => $saleId,
                'tanggal' => $now->toDateTimeString(),
                'total' => $total,
                'status' => 'Selesai',
                'ms_user_id_user' => (string) $cashierId,
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $quantity = $line['quantity'];

                DB::table('tr_detail_pesanan')->insert([
                    'id_detail' => $detailId++,
                    'id_pesanan' => (string) $saleId,
                    'id_produk' => (string) $product->id_produk,
                    'jumlah' => $quantity,
                    'harga' => $line['price'],
                    'sub_total' => $line['sub_total'],
                ]);

                $product->stok = (int) $product->stok - $quantity;
                $product->save();

                $stockId = (int) DB::table('tr_stok')->lockForUpdate()->max('id_stok') + 1;
                if ($stockId > 2147483647) {
                    throw new RuntimeException('Nomor catatan stok telah mencapai batas maksimum database.');
                }

                DB::table('tr_stok')->insert([
                    'id_stok' => $stockId,
                    'id_produk' => (string) $product->id_produk,
                    'id_kategori' => $product->id_kategori,
                    'tanggal' => $now->toDateString(),
                    'jumlah' => -$quantity,
                    'keterangan' => 'Penjualan #'.$saleId,
                    'ms_user_id_user' => (string) $cashierId,
                    'ms_produk_id_produk' => (string) $product->id_produk,
                ]);
            }

            DB::table('tr_pembayaran')->insert([
                'id_pembayaran' => $paymentId,
                'id_pesanan' => (string) $saleId,
                'metode' => $method,
                'jumlah' => $amountPaid,
                'status_pembayaran' => 'Lunas',
                'waktu_bayar' => $now->toDateTimeString(),
            ]);

            return $saleId;
        });
    }
}
