<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $stocks = DB::table('tr_stok as stock')
            ->leftJoin('ms_produk as product', 'product.id_produk', '=', 'stock.ms_produk_id_produk')
            ->leftJoin('ms_kategori as category', 'category.id_kategori', '=', 'stock.id_kategori')
            ->leftJoin('ms_user as user', 'user.id_user', '=', 'stock.ms_user_id_user')
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('product.nama_produk', 'like', '%'.$search.'%')
                        ->orWhere('category.nama_kategori', 'like', '%'.$search.'%')
                        ->orWhere('stock.keterangan', 'like', '%'.$search.'%')
                        ->orWhere('user.nama_lengkap', 'like', '%'.$search.'%');
                });
            })
            ->select([
                'stock.id_stok',
                'stock.id_produk',
                'stock.id_kategori',
                'stock.tanggal',
                'stock.jumlah',
                'stock.keterangan',
                'stock.ms_user_id_user',
                'stock.ms_produk_id_produk',
                'product.nama_produk',
                'category.nama_kategori',
                'user.nama_lengkap as nama_petugas',
            ])
            ->orderByDesc('stock.tanggal')
            ->orderByDesc('stock.id_stok')
            ->paginate(15)
            ->withQueryString();

        $products = Product::query()
            ->with('category')
            ->orderBy('nama_produk')
            ->get();

        return view('stocks.index', [
            'stocks' => $stocks,
            'products' => $products,
            'search' => $filters['q'] ?? '',
            'productCount' => Product::query()->count(),
            'totalStock' => (int) Product::query()->sum('stok'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_produk' => ['required', 'integer', Rule::exists('ms_produk', 'id_produk')],
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'keterangan' => ['nullable', 'string', 'max:45'],
        ], [
            'id_produk.required' => 'Pilih produk yang akan ditambahkan stoknya.',
            'id_produk.exists' => 'Produk yang dipilih tidak ditemukan.',
            'tanggal.required' => 'Tanggal stok masuk wajib diisi.',
            'jumlah.required' => 'Jumlah stok masuk wajib diisi.',
            'jumlah.integer' => 'Jumlah stok harus berupa angka bulat.',
            'jumlah.min' => 'Jumlah stok masuk minimal 1.',
            'keterangan.max' => 'Keterangan maksimal 45 karakter.',
        ]);

        DB::transaction(function () use ($data, $request): void {
            $product = Product::query()
                ->lockForUpdate()
                ->findOrFail($data['id_produk']);
            $currentStock = max((int) $product->stok, 0);
            $incomingStock = (int) $data['jumlah'];

            if ($currentStock > 2147483647 - $incomingStock) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Jumlah stok melebihi batas yang dapat disimpan.',
                ]);
            }

            $nextId = (int) DB::table('tr_stok')->max('id_stok') + 1;
            if ($nextId > 2147483647) {
                throw new RuntimeException('ID pencatatan stok telah mencapai batas maksimum database.');
            }

            DB::table('tr_stok')->insert([
                'id_stok' => $nextId,
                'id_produk' => (string) $product->id_produk,
                'id_kategori' => $product->id_kategori,
                'tanggal' => $data['tanggal'],
                'jumlah' => $incomingStock,
                'keterangan' => $data['keterangan'] ?? null,
                'ms_user_id_user' => (string) $request->user()->getAuthIdentifier(),
                'ms_produk_id_produk' => (string) $product->id_produk,
            ]);

            $product->stok = $currentStock + $incomingStock;
            $product->save();
        });

        return redirect()->route('stocks.index')->with('status', 'Stok masuk berhasil dicatat.');
    }
}
