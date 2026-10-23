<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = now('Asia/Jakarta')->toDateString();
        $chartStart = now('Asia/Jakarta')->subDays(29)->startOfDay();
        $isCashier = $request->user()->role === 'Kasir';
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $search = $filters['q'] ?? '';

        $salesToday = DB::table('tr_pesanan')
            ->where('status', 'Selesai')
            ->whereDate('tanggal', $today);
        $paymentTotals = DB::table('tr_pembayaran as payment')
            ->join('tr_pesanan as sale', 'sale.id_pesanan', '=', 'payment.id_pesanan')
            ->where('sale.status', 'Selesai')
            ->whereDate('payment.waktu_bayar', $today)
            ->select('payment.metode', DB::raw('SUM(sale.total) as total'))
            ->groupBy('payment.metode')
            ->pluck('total', 'metode');
        $dailyTotals = DB::table('tr_pesanan')
            ->where('status', 'Selesai')
            ->where('tanggal', '>=', $chartStart->toDateTimeString())
            ->selectRaw('DATE(tanggal) as sale_date, SUM(total) as total')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->keyBy('sale_date');
        $chartDays = collect(range(0, 29))->map(function (int $offset) use ($chartStart, $dailyTotals): array {
            $date = $chartStart->copy()->addDays($offset)->toDateString();

            return [
                'date' => $date,
                'label' => $chartStart->copy()->addDays($offset)->format('d'),
                'total' => (int) ($dailyTotals->get($date)->total ?? 0),
            ];
        });

        $recentSales = DB::table('tr_pesanan as sale')
            ->leftJoin('ms_user as user', 'user.id_user', '=', 'sale.ms_user_id_user')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('sale.id_pesanan', 'like', '%'.$search.'%')
                        ->orWhere('sale.status', 'like', '%'.$search.'%')
                        ->orWhere('user.nama_lengkap', 'like', '%'.$search.'%');
                });
            })
            ->select('sale.id_pesanan', 'sale.tanggal', 'sale.total', 'sale.status', 'user.nama_lengkap as nama_kasir')
            ->orderByDesc('sale.tanggal')
            ->orderByDesc('sale.id_pesanan')
            ->limit(5)
            ->get();

        $topProducts = DB::table('tr_detail_pesanan as detail')
            ->join('tr_pesanan as sale', 'sale.id_pesanan', '=', 'detail.id_pesanan')
            ->leftJoin('ms_produk as product', 'product.id_produk', '=', 'detail.id_produk')
            ->leftJoin('ms_kategori as category', 'category.id_kategori', '=', 'product.id_kategori')
            ->where('sale.status', 'Selesai')
            ->when($search !== '', fn ($query) => $query->where('product.nama_produk', 'like', '%'.$search.'%'))
            ->select(
                'product.id_produk',
                'product.nama_produk',
                'category.nama_kategori',
                DB::raw('SUM(detail.jumlah) as quantity'),
                DB::raw('SUM(detail.sub_total) as total')
            )
            ->groupBy('product.id_produk', 'product.nama_produk', 'category.nama_kategori')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get();

        $chartMax = max((int) $chartDays->max('total'), 1);
        $chartPoints = $chartDays->values()->map(function (array $day, int $index) use ($chartDays, $chartMax): string {
            $x = $chartDays->count() > 1 ? 12 + ($index * 576 / ($chartDays->count() - 1)) : 12;
            $y = 174 - ($day['total'] / $chartMax * 150);

            return number_format($x, 2, '.', '').','.number_format($y, 2, '.', '');
        });
        $totalPaymentToday = (int) $paymentTotals->sum();
        $cashTotalToday = (int) $paymentTotals->get('Tunai', 0);
        $qrisTotalToday = (int) $paymentTotals->get('QRIS', 0);
        $otherPaymentToday = max($totalPaymentToday - $cashTotalToday - $qrisTotalToday, 0);

        return view('dashboard.index', [
            'isCashier' => $isCashier,
            'user' => $request->user()->loadMissing('shift'),
            'search' => $search,
            'productCount' => Product::query()->count(),
            'totalStock' => (int) Product::query()->sum('stok'),
            'lowStockCount' => Product::query()->where('stok', '<=', 5)->count(),
            'employeeCount' => $request->user()->can('manage-employees')
                ? User::query()->count()
                : null,
            'salesTodayCount' => $salesToday->count(),
            'salesTodayTotal' => (int) $salesToday->sum('total'),
            'cashTotalToday' => $cashTotalToday,
            'qrisTotalToday' => $qrisTotalToday,
            'otherPaymentToday' => $otherPaymentToday,
            'totalPaymentToday' => $totalPaymentToday,
            'chartDays' => $chartDays,
            'chartPoints' => $chartPoints->implode(' '),
            'recentSales' => $recentSales,
            'topProducts' => $topProducts,
            'pendingOrderCount' => $isCashier
                ? DB::table('tr_pesanan')->where('status', 'Pending')->count()
                : null,
        ]);
    }
}
