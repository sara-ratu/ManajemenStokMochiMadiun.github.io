<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Beranda | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page catalog-page dashboard-page">
    @php
        $cashPercent = $totalPaymentToday > 0 ? $cashTotalToday / $totalPaymentToday * 100 : 0;
        $qrisPercent = $totalPaymentToday > 0 ? $qrisTotalToday / $totalPaymentToday * 100 : 0;
        $otherPercent = max(100 - $cashPercent - $qrisPercent, 0);
    @endphp
    <header class="catalog-topbar dashboard-topbar">
        <a class="catalog-brand" href="{{ route('dashboard') }}" aria-label="Beranda Mochi Madiun">
            <img class="mochi-logo" src="{{ asset('images/mochi-madiun-logo.png') }}" alt="">
            @php($dashboardNow = now('Asia/Jakarta'))
            <small><time data-live-clock datetime="{{ $dashboardNow->toIso8601String() }}">{{ $dashboardNow->locale('id')->translatedFormat('l, d F Y') }}</time></small>
        </a>
        <form class="catalog-search dashboard-search" method="GET" action="{{ route('dashboard') }}" role="search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            <input name="q" type="search" value="{{ $search }}" placeholder="Cari produk, pesanan, atau nama kasir..." maxlength="100" aria-label="Cari produk, pesanan, atau nama kasir">
        </form>
        <div class="dashboard-user">
            @if ($isCashier)
                <a class="dashboard-notification" href="{{ route('customer-orders.cashier.index') }}" aria-label="Lihat order pelanggan">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4"/></svg>
                    @if ($pendingOrderCount > 0)<span></span>@endif
                </a>
            @else
                <span class="dashboard-user-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->nama_lengkap ?: 'U', 0, 1)) }}</span>
            @endif
            <div>
                <strong>{{ $user->nama_lengkap ?: 'Pengguna' }}</strong>
                <small>
                    @if ($isCashier)
                        Kasir{{ $user->shift?->nama_shift ? ' · Shift '.$user->shift->nama_shift : '' }}
                    @else
                        Administrator
                    @endif
                </small>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="dashboard-logout" type="submit">Keluar</button>
            </form>
        </div>
    </header>

    <div class="products-app-shell dashboard-shell">
        <aside class="app-sidebar dashboard-sidebar">
            <p class="sidebar-section-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('dashboard') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z"/></svg>
                    <span>Beranda</span>
                </a>
                <a class="sidebar-nav-link" href="{{ route('sales.index') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h3m2 0h3m-8 4h8"/></svg>
                    <span>{{ $isCashier ? 'Penjualan' : 'Laporan' }}</span>
                </a>
                @can('cashier')
                    <a class="sidebar-nav-link" href="{{ route('customer-orders.cashier.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/><path d="m8 19 4 3 4-3"/></svg>
                        <span>Order</span>
                        @if ($pendingOrderCount > 0)<span class="dashboard-sidebar-count">{{ $pendingOrderCount }}</span>@endif
                    </a>
                    <a class="sidebar-nav-link" href="{{ route('stocks.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4z"/><path d="M4 7l2-4h12l2 4M12 11v6m-3-3h6"/></svg>
                        <span>Kelola Stok</span>
                    </a>
                @endcan
                @can('manage-products')
                    <a class="sidebar-nav-link" href="{{ route('products.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4.5 7.5 7.5 4 7.5-4M12 12v9"/></svg>
                        <span>Kelola Produk</span>
                    </a>
                    <a class="sidebar-nav-link" href="{{ route('employees.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 4a4 4 0 0 1 0 8m2 2a7 7 0 0 1 4 7"/></svg>
                        <span>Karyawan</span>
                    </a>
                @endcan
                <a class="sidebar-nav-link" href="{{ route('profile.show') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <span>Profil</span>
                </a>
            </nav>
            <div class="sidebar-footer">Mochi Madiun <span>Manajemen Toko</span></div>
        </aside>

        <div class="products-workspace dashboard-workspace">
            <main class="products-main dashboard-main">
                <section class="dashboard-summary" aria-label="Ringkasan penjualan hari ini">
                    <article class="dashboard-stat-card">
                        <div class="dashboard-stat-heading"><span>Pendapatan</span><span class="dashboard-stat-period">Hari ini</span></div>
                        <div class="dashboard-stat-value"><span class="dashboard-stat-icon dashboard-stat-icon-pink" aria-hidden="true">Rp</span><strong>Rp{{ number_format($salesTodayTotal, 0, ',', '.') }}</strong></div>
                    </article>
                    <article class="dashboard-stat-card">
                        <div class="dashboard-stat-heading"><span>Pesanan Selesai</span><span class="dashboard-stat-period">Hari ini</span></div>
                        <div class="dashboard-stat-value"><span class="dashboard-stat-icon dashboard-stat-icon-purple" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 4h2l2 11h11l3-8H6M9 19h.01M17 19h.01"/></svg></span><strong>{{ number_format($salesTodayCount, 0, ',', '.') }}</strong></div>
                    </article>
                    <article class="dashboard-stat-card">
                        <div class="dashboard-stat-heading"><span>Tunai</span><span class="dashboard-stat-period">Pembayaran</span></div>
                        <div class="dashboard-stat-value"><span class="dashboard-stat-icon dashboard-stat-icon-green" aria-hidden="true">Rp</span><strong>Rp{{ number_format($cashTotalToday, 0, ',', '.') }}</strong></div>
                    </article>
                    <article class="dashboard-stat-card">
                        <div class="dashboard-stat-heading"><span>QRIS</span><span class="dashboard-stat-period">Pembayaran</span></div>
                        <div class="dashboard-stat-value"><span class="dashboard-stat-icon dashboard-stat-icon-orange" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zm4 0h2v6h-2zm-4 4h2v2h-2"/></svg></span><strong>Rp{{ number_format($qrisTotalToday, 0, ',', '.') }}</strong></div>
                    </article>
                </section>

                <section class="dashboard-analytics">
                    <article class="dashboard-panel dashboard-sales-chart">
                        <div class="dashboard-panel-heading">
                            <div><h1>Grafik Penjualan</h1><p>Penjualan selesai selama 30 hari terakhir</p></div>
                            <span class="dashboard-chart-period">30 hari terakhir</span>
                        </div>
                        <div class="dashboard-chart">
                            <div class="dashboard-chart-axis" aria-hidden="true">
                                <span>Rp{{ number_format(max((int) $chartDays->max('total'), 0), 0, ',', '.') }}</span>
                                <span>Rp{{ number_format((int) floor($chartDays->max('total') / 2), 0, ',', '.') }}</span>
                                <span>Rp0</span>
                            </div>
                            <svg viewBox="0 0 600 190" preserveAspectRatio="none" role="img" aria-label="Grafik pendapatan harian 30 hari terakhir">
                                <defs>
                                    <linearGradient id="dashboard-chart-fill" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="#ff6687" stop-opacity=".65"/>
                                        <stop offset="100%" stop-color="#ff6687" stop-opacity=".03"/>
                                    </linearGradient>
                                </defs>
                                <path class="dashboard-chart-gridline" d="M0 24H600M0 94H600M0 174H600"/>
                                <polygon points="0,174 {{ $chartPoints }} 600,174" fill="url(#dashboard-chart-fill)"/>
                                <polyline points="{{ $chartPoints }}" fill="none" stroke="#fb5c7e" stroke-width="3" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="dashboard-chart-labels" aria-hidden="true">
                            @foreach ($chartDays->filter(fn (array $day, int $index) => $index % 5 === 0 || $index === 29) as $day)
                                <span>{{ $day['label'] }}</span>
                            @endforeach
                        </div>
                    </article>

                    <article class="dashboard-panel dashboard-payment-panel">
                        <div class="dashboard-panel-heading">
                            <div><h2>Metode Pembayaran</h2><p>Jumlah pembayaran hari ini</p></div>
                        </div>
                        <div class="dashboard-donut" style="--cash-percent: {{ number_format($cashPercent, 2, '.', '') }}%; --qris-percent: {{ number_format($qrisPercent, 2, '.', '') }}%;">
                            <div><strong>{{ $totalPaymentToday > 0 ? 'Rp'.number_format($totalPaymentToday, 0, ',', '.') : 'Rp0' }}</strong><span>Jumlah keseluruhan</span></div>
                        </div>
                        <div class="dashboard-payment-legend">
                            <span><i class="dashboard-legend-cash"></i>Tunai {{ number_format($cashPercent, 0) }}%</span>
                            <span><i class="dashboard-legend-qris"></i>QRIS {{ number_format($qrisPercent, 0) }}%</span>
                            <span><i class="dashboard-legend-other"></i>Lainnya {{ number_format($otherPercent, 0) }}%</span>
                        </div>
                        <p class="dashboard-other-total">Pembayaran lain: Rp{{ number_format($otherPaymentToday, 0, ',', '.') }}</p>
                    </article>
                </section>

                <section class="dashboard-lists">
                    <article class="dashboard-panel dashboard-recent-panel">
                        <div class="dashboard-panel-heading">
                            <div><h2>Transaksi Terakhir</h2><p>Pesanan terbaru yang tercatat</p></div>
                            <a href="{{ route('sales.index') }}">Lihat seluruhnya <span aria-hidden="true">→</span></a>
                        </div>
                        <div class="catalog-table-scroll">
                            <table class="dashboard-data-table">
                                <thead><tr><th scope="col">ID Pesanan</th><th scope="col">Tanggal</th><th scope="col">Jumlah</th><th scope="col">Status</th></tr></thead>
                                <tbody>
                                    @forelse ($recentSales as $sale)
                                        <tr>
                                            <td>#{{ $sale->id_pesanan }}</td>
                                            <td>{{ \Illuminate\Support\Carbon::parse($sale->tanggal)->locale('id')->translatedFormat('d M · H.i') }}</td>
                                            <td>Rp{{ number_format((int) $sale->total, 0, ',', '.') }}</td>
                                            <td><span class="dashboard-order-status dashboard-order-status-{{ \Illuminate\Support\Str::slug($sale->status) }}">{{ $sale->status === 'Pending' ? 'Menunggu' : $sale->status }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td class="dashboard-table-empty" colspan="4">{{ $search !== '' ? 'Tidak ada transaksi yang cocok.' : 'Belum ada transaksi.' }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="dashboard-panel dashboard-products-panel">
                        <div class="dashboard-panel-heading">
                            <div><h2>Produk Terlaris</h2><p>Berdasarkan jumlah terjual</p></div>
                            @can('manage-products')
                                <a href="{{ route('products.index') }}">Produk <span aria-hidden="true">→</span></a>
                            @endcan
                        </div>
                        <div class="catalog-table-scroll">
                            <table class="dashboard-data-table">
                                <thead><tr><th scope="col">Produk</th><th scope="col">Kategori</th><th scope="col">Terjual</th></tr></thead>
                                <tbody>
                                    @forelse ($topProducts as $product)
                                        <tr>
                                            <td><span class="dashboard-product-id">#{{ $product->id_produk }}</span> {{ $product->nama_produk ?: 'Produk tanpa nama' }}</td>
                                            <td>{{ $product->nama_kategori ?: '—' }}</td>
                                            <td>{{ number_format((int) $product->quantity, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="dashboard-table-empty" colspan="3">{{ $search !== '' ? 'Produk tidak ditemukan.' : 'Belum ada data penjualan produk.' }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </article>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
