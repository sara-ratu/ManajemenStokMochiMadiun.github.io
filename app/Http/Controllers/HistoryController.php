<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', 'in:penjualan,stok'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'in:Selesai,Batal,Pending'],
        ]);

        $tab = $filters['tab'] ?? 'penjualan';
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $status = $filters['status'] ?? null;

        $sales = null;
        $stocks = null;

        if ($tab === 'stok') {
            $stocks = DB::table('tr_stok as stock')
                ->leftJoin('ms_produk as product', 'product.id_produk', '=', 'stock.ms_produk_id_produk')
                ->leftJoin('ms_user as user', 'user.id_user', '=', 'stock.ms_user_id_user')
                ->select([
                    'stock.id_stok',
                    'stock.tanggal',
                    'stock.jumlah',
                    'stock.keterangan',
                    'product.nama_produk',
                    'user.nama_lengkap as nama_petugas',
                ])
                ->when($startDate, fn ($query, $value) => $query->whereDate('stock.tanggal', '>=', $value))
                ->when($endDate, fn ($query, $value) => $query->whereDate('stock.tanggal', '<=', $value))
                ->orderByDesc('stock.tanggal')
                ->orderByDesc('stock.id_stok')
                ->paginate(10)
                ->withQueryString();
        } else {
            $sales = DB::table('tr_pesanan as sale')
                ->leftJoin('ms_user as user', 'user.id_user', '=', 'sale.ms_user_id_user')
                ->select([
                    'sale.id_pesanan',
                    'sale.tanggal',
                    'sale.total',
                    'sale.status',
                    'sale.ms_user_id_user',
                    'user.nama_lengkap as nama_kasir',
                    DB::raw('(select coalesce(sum(d.jumlah), 0) from tr_detail_pesanan d where d.id_pesanan = sale.id_pesanan) as jumlah_item'),
                    DB::raw('(select p.metode from tr_pembayaran p where p.id_pesanan = sale.id_pesanan order by p.id_pembayaran desc limit 1) as metode'),
                ])
                ->when($status, fn ($query, $value) => $query->where('sale.status', $value))
                ->when($startDate, fn ($query, $value) => $query->whereDate('sale.tanggal', '>=', $value))
                ->when($endDate, fn ($query, $value) => $query->whereDate('sale.tanggal', '<=', $value))
                ->orderByDesc('sale.tanggal')
                ->orderByDesc('sale.id_pesanan')
                ->paginate(10)
                ->withQueryString();
        }

        $salesCount = DB::table('tr_pesanan')
            ->where('status', 'Selesai')
            ->when($startDate, fn ($query, $value) => $query->whereDate('tanggal', '>=', $value))
            ->when($endDate, fn ($query, $value) => $query->whereDate('tanggal', '<=', $value))
            ->count();

        return view('history.index', [
            'tab' => $tab,
            'sales' => $sales,
            'stocks' => $stocks,
            'salesCount' => $salesCount,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status,
        ]);
    }
}