<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(): View
    {
        $sales = DB::table('tr_pesanan as sale')
            ->leftJoin('ms_user as user', 'user.id_user', '=', 'sale.ms_user_id_user')
            ->where('sale.status', 'Selesai')
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
            ->limit(10)
            ->get();

        return view('history.index', [
            'sales' => $sales,
            'salesCount' => DB::table('tr_pesanan')->where('status', 'Selesai')->count(),
        ]);
    }
}
