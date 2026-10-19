<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class SalesController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $products = Product::query()
            ->with('category')
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nama_produk', 'like', '%'.$search.'%')
                        ->orWhereHas('category', function ($query) use ($search): void {
                            $query->where('nama_kategori', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderBy('nama_produk')
            ->get();

        $sales = DB::table('tr_pesanan as sale')
            ->leftJoin('ms_user as user', 'user.id_user', '=', 'sale.ms_user_id_user')
            ->select([
                'sale.id_pesanan',
                'sale.tanggal',
                'sale.total',
                'sale.status',
                'sale.ms_user_id_user',
                'user.nama_lengkap as nama_kasir',
            ])
            ->orderByDesc('sale.tanggal')
            ->orderByDesc('sale.id_pesanan')
            ->paginate(10, ['*'], 'riwayat')
            ->withQueryString();

        return view('sales.index', [
            'products' => $products,
            'sales' => $sales,
            'search' => $filters['q'] ?? '',
            'productCount' => Product::query()->count(),
            'salesCount' => DB::table('tr_pesanan')->count(),
            'salesTotal' => (int) DB::table('tr_pesanan')->where('status', 'Selesai')->sum('total'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_produk' => ['required', 'integer', 'distinct'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'metode' => ['required', 'in:Tunai,Transfer,QRIS'],
            'jumlah_bayar' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ], [
            'items.required' => 'Tambahkan setidaknya satu produk ke keranjang.',
            'items.min' => 'Tambahkan setidaknya satu produk ke keranjang.',
            'items.*.id_produk.required' => 'Produk keranjang tidak valid.',
            'items.*.id_produk.distinct' => 'Produk yang sama tidak boleh muncul dua kali di keranjang.',
            'items.*.jumlah.required' => 'Jumlah produk wajib diisi.',
            'items.*.jumlah.min' => 'Jumlah produk minimal 1.',
            'metode.required' => 'Pilih metode pembayaran.',
            'metode.in' => 'Metode pembayaran yang dipilih tidak valid.',
            'jumlah_bayar.required' => 'Masukkan jumlah pembayaran.',
            'jumlah_bayar.integer' => 'Jumlah pembayaran harus berupa bilangan bulat.',
        ]);

        $saleId = DB::transaction(function () use ($data, $request): int {
            $itemIds = collect($data['items'])
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
            foreach ($data['items'] as $item) {
                $product = $products->get((int) $item['id_produk']);
                $quantity = (int) $item['jumlah'];
                $availableStock = max((int) $product->stok, 0);

                if ($availableStock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Stok '.$product->nama_produk.' tidak mencukupi. Tersedia '.$availableStock.' pcs.',
                    ]);
                }

                $price = (int) $product->harga;
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

            if ((int) $data['jumlah_bayar'] < $total) {
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
                'ms_user_id_user' => (string) $request->user()->getAuthIdentifier(),
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
                    'ms_user_id_user' => (string) $request->user()->getAuthIdentifier(),
                    'ms_produk_id_produk' => (string) $product->id_produk,
                ]);
            }

            DB::table('tr_pembayaran')->insert([
                'id_pembayaran' => $paymentId,
                'id_pesanan' => (string) $saleId,
                'metode' => $data['metode'],
                'jumlah' => (int) $data['jumlah_bayar'],
                'status_pembayaran' => 'Lunas',
                'waktu_bayar' => $now->toDateTimeString(),
            ]);

            return $saleId;
        });

        return redirect()->route('sales.show', $saleId)->with('status', 'Transaksi berhasil disimpan.');
    }

    public function show(int $sale): View
    {
        $order = DB::table('tr_pesanan as sale')
            ->leftJoin('ms_user as user', 'user.id_user', '=', 'sale.ms_user_id_user')
            ->where('sale.id_pesanan', $sale)
            ->select([
                'sale.id_pesanan',
                'sale.tanggal',
                'sale.total',
                'sale.status',
                'sale.ms_user_id_user',
                'user.nama_lengkap as nama_kasir',
            ])
            ->first();

        abort_if($order === null, 404);

        $details = DB::table('tr_detail_pesanan as detail')
            ->leftJoin('ms_produk as product', 'product.id_produk', '=', 'detail.id_produk')
            ->where('detail.id_pesanan', (string) $sale)
            ->select([
                'detail.id_detail',
                'detail.id_produk',
                'detail.jumlah',
                'detail.harga',
                'detail.sub_total',
                'product.nama_produk',
            ])
            ->orderBy('detail.id_detail')
            ->get();

        $payment = DB::table('tr_pembayaran')
            ->where('id_pesanan', (string) $sale)
            ->orderByDesc('id_pembayaran')
            ->first();

        return view('sales.show', compact('order', 'details', 'payment'));
    }
}
