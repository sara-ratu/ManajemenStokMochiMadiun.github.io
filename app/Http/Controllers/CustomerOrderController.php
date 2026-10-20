<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\SaleProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
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
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id_produk' => ['required', 'integer', 'distinct'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:100'],
        ], [
            'items.required' => 'Pilih setidaknya satu produk.',
            'items.min' => 'Pilih setidaknya satu produk.',
        ]);

        $orderId = DB::transaction(function () use ($data): int {
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

            $orderId = (int) DB::table('tr_pesanan')->lockForUpdate()->max('id_pesanan') + 1;
            if ($orderId > 2147483647) {
                throw new \RuntimeException('Nomor pesanan telah mencapai batas maksimum database.');
            }

            $now = now('Asia/Jakarta');
            DB::table('tr_pesanan')->insert([
                'id_pesanan' => $orderId,
                'tanggal' => $now->toDateTimeString(),
                'total' => $total,
                'status' => 'Pending',
                'ms_user_id_user' => null,
            ]);

            $detailId = (int) DB::table('tr_detail_pesanan')->lockForUpdate()->max('id_detail') + 1;
            if ($detailId > 2147483647 - count($lines) + 1) {
                throw new \RuntimeException('Nomor detail pesanan telah mencapai batas maksimum database.');
            }

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];
                DB::table('tr_detail_pesanan')->insert([
                    'id_detail' => $detailId++,
                    'id_pesanan' => (string) $orderId,
                    'id_produk' => (string) $product->id_produk,
                    'jumlah' => $line['quantity'],
                    'harga' => $line['price'],
                    'sub_total' => $line['sub_total'],
                ]);
            }

            return $orderId;
        });

        $expiresAt = now()->addHours(24);

        return redirect()->away(URL::temporarySignedRoute(
            'customer-orders.track',
            $expiresAt,
            ['order' => $orderId],
        ));
    }

    public function track(int $order): View
    {
        $customerOrder = DB::table('tr_pesanan')->where('id_pesanan', $order)->first();
        abort_if($customerOrder === null, 404);

        $items = $this->orderItems($order);
        $statusUrl = URL::temporarySignedRoute(
            'customer-orders.status',
            now()->addHours(24),
            ['order' => $order],
        );

        return view('customer-orders.track', compact('customerOrder', 'items', 'statusUrl'));
    }

    public function trackStatus(int $order): JsonResponse
    {
        $customerOrder = DB::table('tr_pesanan')
            ->where('id_pesanan', $order)
            ->first(['status']);
        abort_if($customerOrder === null, 404);

        return response()->json(['status' => $customerOrder->status])
            ->header('Cache-Control', 'no-store, private');
    }

    public function cashierIndex(): View
    {
        $orders = DB::table('tr_pesanan')
            ->where('status', 'Pending')
            ->orderBy('tanggal')
            ->orderBy('id_pesanan')
            ->get();

        $itemsByOrder = DB::table('tr_detail_pesanan as detail')
            ->leftJoin('ms_produk as product', 'product.id_produk', '=', 'detail.id_produk')
            ->whereIn('detail.id_pesanan', $orders->pluck('id_pesanan')->map(fn ($id): string => (string) $id))
            ->select([
                'detail.id_pesanan',
                'detail.id_produk',
                'detail.jumlah',
                'detail.harga',
                'detail.sub_total',
                'product.nama_produk',
            ])
            ->orderBy('detail.id_detail')
            ->get()
            ->groupBy('id_pesanan');

        foreach ($orders as $order) {
            $order->items = $itemsByOrder->get((string) $order->id_pesanan, collect());
        }

        $latestOrderId = (int) DB::table('tr_pesanan')->where('status', 'Pending')->max('id_pesanan');

        return view('customer-orders.cashier-index', compact('orders', 'latestOrderId'));
    }

    public function notifications(): JsonResponse
    {
        return response()->json([
            'pending_count' => DB::table('tr_pesanan')->where('status', 'Pending')->count(),
            'latest_order_id' => (int) DB::table('tr_pesanan')->where('status', 'Pending')->max('id_pesanan'),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function reject(int $order): RedirectResponse
    {
        $updated = DB::table('tr_pesanan')
            ->where('id_pesanan', $order)
            ->where('status', 'Pending')
            ->update(['status' => 'Batal']);

        if ($updated === 0) {
            abort_unless(DB::table('tr_pesanan')->where('id_pesanan', $order)->exists(), 404);

            return back()->with('error', 'Pesanan sudah ditangani atau statusnya telah berubah.');
        }

        return redirect()->route('customer-orders.cashier.index')->with('status', 'Pesanan dibatalkan.');
    }

    public function cashierShow(int $order): View
    {
        $customerOrder = DB::table('tr_pesanan')->where('id_pesanan', $order)->first();
        abort_if($customerOrder === null, 404);

        $items = $this->orderItems($order);

        return view('customer-orders.cashier-show', compact('customerOrder', 'items'));
    }

    public function complete(Request $request, int $order, SaleProcessor $saleProcessor): RedirectResponse
    {
        $data = $request->validate([
            'metode' => ['required', 'in:Tunai,Transfer,QRIS'],
            'jumlah_bayar' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ]);

        $saleId = $saleProcessor->completePendingOrder(
            $order,
            $data['metode'],
            (int) $data['jumlah_bayar'],
            $request->user()->getAuthIdentifier(),
        );

        return redirect()->route('sales.show', $saleId)
            ->with('status', 'Pesanan pelanggan selesai diproses.');
    }

    private function orderItems(int $order): Collection
    {
        return DB::table('tr_detail_pesanan as detail')
            ->leftJoin('ms_produk as product', 'product.id_produk', '=', 'detail.id_produk')
            ->where('detail.id_pesanan', (string) $order)
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
    }
}
