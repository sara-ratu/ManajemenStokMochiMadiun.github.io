<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Riwayat | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page catalog-page stock-page">
    <header class="catalog-topbar">
        <a class="catalog-brand" href="{{ route('dashboard') }}" aria-label="Beranda Mochi Madiun">
            <img class="mochi-logo" src="{{ asset('images/mochi-madiun-logo.png') }}" alt="">
            @php($historyNow = now('Asia/Jakarta'))
            <small><time data-live-clock datetime="{{ $historyNow->toIso8601String() }}">{{ $historyNow->locale('id')->translatedFormat('l, d F Y') }} · {{ $historyNow->format('H.i') }}</time></small>
        </a>
    </header>

    <div class="products-app-shell">
        <aside class="app-sidebar">
            <p class="sidebar-section-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <a class="sidebar-nav-link" href="{{ route('dashboard') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z"/></svg>
                    <span>Beranda</span>
                </a>
                <a class="sidebar-nav-link" href="{{ route('sales.index') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h3m2 0h3m-8 4h8"/></svg>
                    <span>{{ auth()->user()->can('cashier') ? 'Transaksi' : 'Laporan Penjualan' }}</span>
                </a>
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('history.index') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9 9 0 0 0-9 9zm0 0h7m-7 0 3-3m-3 3 3 3M12 3v7m0 0 3 3m-3-3-3 3"/></svg>
                    <span>Riwayat</span>
                </a>
                @can('cashier')
                    <a class="sidebar-nav-link" href="{{ route('customer-orders.cashier.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/><path d="m8 19 4 3 4-3"/></svg>
                        <span>Order</span>
                    </a>
                    <a class="sidebar-nav-link" href="{{ route('stocks.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4z"/><path d="M4 7l2-4h12l2 4M12 11v6m-3-3h6"/></svg>
                        <span>Kelola Stok</span>
                    </a>
                @endcan
                <a class="sidebar-nav-link" href="{{ route('profile.show') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <span>Profil</span>
                </a>
            </nav>
            <div class="sidebar-footer">Mochi Madiun <span>Manajemen Toko</span></div>
        </aside>

        <div class="products-workspace">
            <header class="products-topbar">
                <div class="topbar-page-label">Riwayat</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ auth()->user()->nama_lengkap ?: 'Pengguna' }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="logout-button" type="submit">Keluar</button>
                    </form>
                </div>
            </header>

            <main class="products-main stock-main">
                <section class="products-heading stock-heading">
                    <div>
                        <p class="products-eyebrow">RIWAYAT PENJUALAN</p>
                        <p class="products-subtitle">Lihat daftar transaksi penjualan yang telah selesai.</p>
                    </div>
                </section>

                <section class="stock-summary" aria-label="Ringkasan riwayat penjualan">
                    <article class="stock-summary-card">
                        <span class="stock-summary-icon stock-summary-icon-green" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M3 12h18M12 3v18"/><circle cx="12" cy="12" r="9"/></svg>
                        </span>
                        <div><span>Transaksi Selesai</span><strong>{{ number_format($salesCount, 0, ',', '.') }}</strong></div>
                    </article>
                </section>

                <section class="stock-history-card" aria-label="Riwayat transaksi terbaru">
                    <div class="stock-history-heading">
                        <div>
                            <h2>Riwayat Transaksi</h2>
                            <p>Transaksi penjualan yang telah selesai.</p>
                        </div>
                        <a class="stock-clear-filter" href="{{ route('sales.index') }}">Lihat semua</a>
                    </div>
                    <div class="catalog-table-scroll">
                        <table class="stock-table sales-history-table">
                            <thead>
                                <tr>
                                    <th scope="col">ID Pesanan</th>
                                    <th scope="col">Tanggal</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Kasir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sales as $sale)
                                    <tr>
                                        <td class="stock-id-cell">#{{ $sale->id_pesanan }}</td>
                                        <td>{{ \Illuminate\Support\Carbon::parse($sale->tanggal)->locale('id')->translatedFormat('d M Y') }}</td>
                                        <td>Rp{{ number_format((int) $sale->total, 0, ',', '.') }}</td>
                                        <td>{{ $sale->nama_kasir ?: 'Pengguna #'.$sale->ms_user_id_user }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="stock-empty-cell" colspan="4">Belum ada transaksi yang tercatat.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

            </main>
        </div>
    </div>
</body>
</html>
