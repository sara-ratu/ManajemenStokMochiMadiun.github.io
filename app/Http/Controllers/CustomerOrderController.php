<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\SaleProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerOrderController extends Controller
{
    public function create(): View
    {
        $products = Product::query()
            ->with('category')
            ->where('stok', '>', 0)
            ->orderBy('nama_produk')
            ->get();

        return view('customer-orders.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:60'],
            'no_telepon' => ['nullable', 'string', 'max:20'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id_produk' => ['required', 'integer', 'distinct'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:100'],
        ], [
            'nama_pelanggan.required' => 'Nama pemesan wajib diisi.',
            'items.required' => 'Pilih setidaknya satu produk.',
            'items.min' => 'Pilih setidaknya satu produk.',
        ]);

        $token = (string) Str::uuid();
        DB::transaction(function () use ($data, $token): int {
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
                        'items' => 'Harga atau jumlah '.$product->nama_produk.' melebihi batas pesanan.',
                    ]);
                }

                $subTotal = $price * $quantity;
                if ($total > 2147483647 - $subTotal) {
                    throw ValidationException::withMessages([
                        'items' => 'Total pesanan melebihi batas yang dapat disimpan.',
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

            $now = now('Asia/Jakarta');
            $orderId = DB::table('tr_customer_orders')->insertGetId([
                'tracking_token' => $token,
                'nama_pelanggan' => $data['nama_pelanggan'],
                'no_telepon' => $data['no_telepon'] ?? null,
                'total' => $total,
                'status' => 'Menunggu',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];
                DB::table('tr_customer_order_items')->insert([
                    'customer_order_id' => $orderId,
                    'id_produk' => $product->id_produk,
                    'nama_produk' => $product->nama_produk ?: 'Produk tanpa nama',
                    'jumlah' => $line['quantity'],
                    'harga' => $line['price'],
                    'sub_total' => $line['sub_total'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return $orderId;
        });

        return redirect()->route('customer-orders.track', $token)
            ->with('status', 'Pesanan berhasil dikirim ke kasir.');
    }

    public function track(string $token): View
    {
        $order = DB::table('tr_customer_orders')
            ->where('tracking_token', $token)
            ->first();
        abort_if($order === null, 404);

        $items = DB::table('tr_customer_order_items')
            ->where('customer_order_id', $order->id)
            ->orderBy('id')
            ->get();

        return view('customer-orders.track', compact('order', 'items'));
    }

    public function trackStatus(string $token): JsonResponse
    {
        $order = DB::table('tr_customer_orders')
            ->where('tracking_token', $token)
            ->first(['status', 'sale_id']);
        abort_if($order === null, 404);

        return response()->json([
            'status' => $order->status,
            'sale_id' => $order->sale_id,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function cashierIndex(): View
    {
        $orders = DB::table('tr_customer_orders')
            ->whereIn('status', ['Menunggu', 'Diproses'])
            ->orderByRaw("CASE WHEN status = 'Menunggu' THEN 0 ELSE 1 END")
            ->orderBy('created_at')
            ->get();

        $itemsByOrder = DB::table('tr_customer_order_items')
            ->whereIn('customer_order_id', $orders->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('customer_order_id');
        foreach ($orders as $order) {
            $order->items = $itemsByOrder->get($order->id, collect());
        }

        $latestOrderId = (int) DB::table('tr_customer_orders')->max('id');

        return view('customer-orders.cashier-index', compact('orders', 'latestOrderId'));
    }

    public function notifications(): JsonResponse
    {
        return response()->json([
            'pending_count' => DB::table('tr_customer_orders')->where('status', 'Menunggu')->count(),
            'latest_order_id' => (int) DB::table('tr_customer_orders')->max('id'),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function updateStatus(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:Diproses,Ditolak'],
        ]);

        $allowedCurrentStatuses = $data['status'] === 'Ditolak'
            ? ['Menunggu', 'Diproses']
            : ['Menunggu'];
        $updated = DB::table('tr_customer_orders')
            ->where('id', $order)
            ->whereIn('status', $allowedCurrentStatuses)
            ->update([
                'status' => $data['status'],
                'updated_at' => now('Asia/Jakarta'),
            ]);

        if ($updated === 0) {
            abort_unless(DB::table('tr_customer_orders')->where('id', $order)->exists(), 404);

            return back()->with('error', 'Pesanan sudah ditangani atau statusnya telah berubah.');
        }

        return redirect()->route('customer-orders.cashier.show', $order)
            ->with('status', $data['status'] === 'Diproses' ? 'Pesanan diterima.' : 'Pesanan ditolak.');
    }

    public function cashierShow(int $order): View
    {
        $customerOrder = DB::table('tr_customer_orders')->where('id', $order)->first();
        abort_if($customerOrder === null, 404);

        $items = DB::table('tr_customer_order_items')
            ->where('customer_order_id', $order)
            ->orderBy('id')
            ->get();

        return view('customer-orders.cashier-show', compact('customerOrder', 'items'));
    }

    public function complete(Request $request, int $order, SaleProcessor $saleProcessor): RedirectResponse
    {
        $data = $request->validate([
            'metode' => ['required', 'in:Tunai,Transfer,QRIS'],
            'jumlah_bayar' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ]);

        $saleId = DB::transaction(function () use ($data, $order, $request, $saleProcessor): int {
            $customerOrder = DB::table('tr_customer_orders')->where('id', $order)->lockForUpdate()->first();
            abort_if($customerOrder === null, 404);
            abort_unless($customerOrder->status === 'Diproses', 409, 'Pesanan harus diterima sebelum diproses.');

            $items = DB::table('tr_customer_order_items')
                ->where('customer_order_id', $order)
                ->orderBy('id')
                ->get()
                ->map(fn ($item): array => [
                    'id_produk' => $item->id_produk,
                    'jumlah' => $item->jumlah,
                    'harga' => $item->harga,
                ])
                ->all();

            $saleId = $saleProcessor->complete(
                $items,
                $data['metode'],
                (int) $data['jumlah_bayar'],
                $request->user()->getAuthIdentifier(),
            );

            DB::table('tr_customer_orders')->where('id', $order)->update([
                'status' => 'Selesai',
                'sale_id' => $saleId,
                'updated_at' => now('Asia/Jakarta'),
            ]);

            return $saleId;
        });

        return redirect()->route('sales.show', $saleId)
            ->with('status', 'Pesanan pelanggan selesai diproses.');
    }
}
