<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\SaleProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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

    public function store(Request $request, SaleProcessor $saleProcessor): RedirectResponse
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

        $saleId = $saleProcessor->complete(
            $data['items'],
            $data['metode'],
            (int) $data['jumlah_bayar'],
            $request->user()->getAuthIdentifier(),
        );

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
