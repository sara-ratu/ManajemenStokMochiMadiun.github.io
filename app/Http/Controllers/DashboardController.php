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
        $isCashier = $request->user()->role === 'Kasir';

        return view('dashboard.index', [
            'isCashier' => $isCashier,
            'productCount' => Product::query()->count(),
            'totalStock' => (int) Product::query()->sum('stok'),
            'lowStockCount' => Product::query()->where('stok', '<=', 5)->count(),
            'employeeCount' => $request->user()->can('manage-employees')
                ? User::query()->count()
                : null,
            'salesTodayCount' => DB::table('tr_pesanan')
                ->where('status', 'Selesai')
                ->whereDate('tanggal', $today)
                ->count(),
            'salesTodayTotal' => (int) DB::table('tr_pesanan')
                ->where('status', 'Selesai')
                ->whereDate('tanggal', $today)
                ->sum('total'),
            'pendingOrderCount' => $isCashier
                ? DB::table('tr_pesanan')->where('status', 'Pending')->count()
                : null,
        ]);
    }
}
