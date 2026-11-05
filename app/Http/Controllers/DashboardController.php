<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShiftChangeRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $now = now('Asia/Jakarta');
        $today = $now->toDateString();
        $isCashier = $request->user()->role === 'Kasir';
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:today'],
        ]);
        $search = $filters['q'] ?? '';
        $chartEnd = Carbon::parse($filters['to'] ?? $now->toDateString(), 'Asia/Jakarta')->endOfDay();
        $chartStart = Carbon::parse(
            $filters['from'] ?? $chartEnd->copy()->subDays(29)->toDateString(),
            'Asia/Jakarta'
        )->startOfDay();
        $chartQuickRanges = collect([7, 30])->map(function (int $days) use ($now, $search, $chartStart, $chartEnd): array {
            $from = $now->copy()->subDays($days - 1)->toDateString();
            $to = $now->toDateString();

            return [
                'label' => $days.' hari',
                'from' => $from,
                'to' => $to,
                'url' => route('dashboard', array_filter([
                    'q' => $search ?: null,
                    'from' => $from,
                    'to' => $to,
                ])),
                'active' => $from === $chartStart->toDateString() && $to === $chartEnd->toDateString(),
            ];
        });
        $rangeDays = (int) $chartStart->diffInDays($chartEnd) + 1;
        $chartInterval = $rangeDays <= 31 ? 'day' : ($rangeDays <= 366 ? 'week' : 'month');
        $chartStep = match ($chartInterval) {
            'day' => 1,
            'week' => 7,
            default => null,
        };
        $chartBuckets = collect();
        $bucketDate = $chartInterval === 'month' ? $chartStart->copy()->startOfMonth() : $chartStart->copy();
        while ($bucketDate->lte($chartEnd)) {
            $chartBuckets->push([
                'key' => $chartInterval === 'month' ? $bucketDate->format('Y-m') : $bucketDate->toDateString(),
                'label' => $bucketDate->locale('id')->translatedFormat($chartInterval === 'month' ? 'M y' : 'd M'),
            ]);
            if ($chartStep !== null) {
                $bucketDate->addDays($chartStep);
            } else {
                $bucketDate->addMonth()->startOfMonth();
            }
        }
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
            ->whereBetween('tanggal', [$chartStart->toDateTimeString(), $chartEnd->toDateTimeString()])
            ->selectRaw('DATE(tanggal) as sale_date, SUM(total) as total')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->keyBy('sale_date');
        $chartTotals = $dailyTotals->reduce(function (array $totals, object $saleDay) use ($chartInterval, $chartStart): array {
            $date = (string) $saleDay->sale_date;
            $key = match ($chartInterval) {
                'day' => $date,
                'week' => $chartStart->copy()->addDays(intdiv((int) $chartStart->diffInDays(Carbon::parse($date)), 7) * 7)->toDateString(),
                default => substr($date, 0, 7),
            };
            $totals[$key] = ($totals[$key] ?? 0) + (int) $saleDay->total;

            return $totals;
        }, []);
        $chartDays = $chartBuckets->map(function (array $bucket) use ($chartTotals): array {
            return [
                'date' => $bucket['key'],
                'label' => $bucket['label'],
                'total' => (int) ($chartTotals[$bucket['key']] ?? 0),
            ];
        });
        $labelStep = max((int) ceil($chartDays->count() / 6), 1);
        $chartLabels = $chartDays
            ->filter(fn (array $bucket, int $index) => $index % $labelStep === 0 || $index === $chartDays->count() - 1)
            ->pluck('label');
        $chartDescription = 'Pendapatan dari '.$chartStart->format('d/m/Y').' sampai '.$chartEnd->format('d/m/Y');

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

        $chartMax = max((int) $chartDays->max('total'), 0);
        $chartScaleMax = max($chartMax, 1);
        $chartPoints = $chartDays->values()->map(function (array $day, int $index) use ($chartDays, $chartScaleMax): string {
            $x = $chartDays->count() > 1 ? 12 + ($index * 576 / ($chartDays->count() - 1)) : 12;
            $y = 174 - ($day['total'] / $chartScaleMax * 150);

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
            'chartLabels' => $chartLabels,
            'chartPoints' => $chartPoints->implode(' '),
            'chartMax' => $chartMax,
            'chartFrom' => $chartStart->toDateString(),
            'chartTo' => $chartEnd->toDateString(),
            'chartQuickRanges' => $chartQuickRanges,
            'chartPeriodDescription' => $chartDescription,
            'recentSales' => $recentSales,
            'topProducts' => $topProducts,
            'pendingOrderCount' => $isCashier
                ? DB::table('tr_pesanan')->where('status', 'Pending')->count()
                : null,
            'pendingShiftRequestCount' => $request->user()->can('manage-employees')
                ? ShiftChangeRequest::query()->where('status', ShiftChangeRequest::STATUS_PENDING)->count()
                : 0,
        ]);
    }
}
