<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Dashboard | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page catalog-page">
    <header class="catalog-topbar">
        <a class="catalog-brand" href="{{ route('dashboard') }}" aria-label="Mochi Madiun">
            <span>MOCHI MADIUN</span>
            @php($dashboardNow = now('Asia/Jakarta'))
            <small><time data-live-clock datetime="{{ $dashboardNow->toIso8601String() }}">{{ $dashboardNow->locale('id')->translatedFormat('l, d F Y') }} · {{ $dashboardNow->format('H.i') }}</time></small>
        </a>
    </header>

    <div class="products-app-shell">
        <aside class="app-sidebar">
            <p class="sidebar-section-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('dashboard') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z"/></svg>
                    <span>Dashboard</span>
                </a>
                @can('manage-employees')
                    <a class="sidebar-nav-link" href="{{ route('employees.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 4a4 4 0 0 1 0 8m2 2a7 7 0 0 1 4 7"/></svg>
                        <span>Data Karyawan</span>
                    </a>
                @endcan
                @can('manage-products')
                    <a class="sidebar-nav-link" href="{{ route('products.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4.5 7.5 7.5 4 7.5-4M12 12v9"/></svg>
                        <span>Produk</span>
                    </a>
                @endcan
                @can('manage-stocks')
                    <a class="sidebar-nav-link" href="{{ route('stocks.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4z"/><path d="M4 7l2-4h12l2 4M12 11v6m-3-3h6"/></svg>
                        <span>Kelola Stok</span>
                    </a>
                @endcan
                @can('cashier')
                    <a class="sidebar-nav-link" href="{{ route('customer-orders.cashier.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/><path d="m8 19 4 3 4-3"/></svg>
                        <span>Order Pelanggan</span>
                    </a>
                @endcan
                <a class="sidebar-nav-link" href="{{ route('sales.index') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h3m2 0h3m-8 4h8"/></svg>
                    <span>{{ $isCashier ? 'Transaksi' : 'Laporan Penjualan' }}</span>
                </a>
                <a class="sidebar-nav-link" href="{{ route('profile.show') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <span>Profil</span>
                </a>
            </nav>
            <div class="sidebar-footer">Mochi Madiun <span>Manajemen Toko</span></div>
        </aside>

        <div class="products-workspace">
            <header class="products-topbar">
                <div class="topbar-page-label">Dashboard</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ auth()->user()->nama_lengkap ?: 'Pengguna' }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="logout-button" type="submit">Keluar</button>
                    </form>
                </div>
            </header>

            <main class="products-main dashboard-main">
                <section class="dashboard-welcome">
                    <div>
                        <p class="products-eyebrow">{{ $isCashier ? 'DASHBOARD KASIR' : 'DASHBOARD SUPER ADMIN' }}</p>
                        <h1>Selamat datang, {{ auth()->user()->nama_lengkap ?: 'Pengguna' }}!</h1>
                        <p class="products-subtitle">{{ $isCashier ? 'Pantau transaksi hari ini dan kelola pesanan pelanggan.' : 'Pantau ringkasan operasional dan akses pengelolaan toko.' }}</p>
                    </div>
                    <span class="dashboard-date">{{ now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}</span>
                </section>

                <section class="dashboard-summary" aria-label="Ringkasan toko">
                    @if ($employeeCount !== null)
                        <article class="dashboard-stat-card">
                            <span class="dashboard-stat-icon dashboard-stat-icon-purple" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 4a4 4 0 0 1 0 8m2 2a7 7 0 0 1 4 7"/></svg>
                            </span>
                            <div><span>Total Karyawan</span><strong>{{ number_format($employeeCount, 0, ',', '.') }}</strong></div>
                        </article>
                    @endif
                    <article class="dashboard-stat-card">
                        <span class="dashboard-stat-icon dashboard-stat-icon-pink" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4.5 7.5 7.5 4 7.5-4M12 12v9"/></svg>
                        </span>
                        <div><span>Total Produk</span><strong>{{ number_format($productCount, 0, ',', '.') }}</strong></div>
                    </article>
                    <article class="dashboard-stat-card">
                        <span class="dashboard-stat-icon dashboard-stat-icon-green" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 3v18m-8-8 8 8 8-8"/></svg>
                        </span>
                        <div><span>Total Stok</span><strong>{{ number_format($totalStock, 0, ',', '.') }} <small>pcs</small></strong></div>
                    </article>
                    <article class="dashboard-stat-card">
                        <span class="dashboard-stat-icon dashboard-stat-icon-orange" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h4"/></svg>
                        </span>
                        <div><span>Transaksi Hari Ini</span><strong>{{ number_format($salesTodayCount, 0, ',', '.') }}</strong></div>
                    </article>
                    <article class="dashboard-stat-card">
                        <span class="dashboard-stat-icon dashboard-stat-icon-green" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M3 6h18M5 6l1 15h12l1-15M9 10v7m6-7v7M8 3h8l1 3H7z"/></svg>
                        </span>
                        <div><span>Omzet Hari Ini</span><strong>Rp{{ number_format($salesTodayTotal, 0, ',', '.') }}</strong></div>
                    </article>
                    @if ($pendingOrderCount !== null)
                        <article class="dashboard-stat-card">
                            <span class="dashboard-stat-icon dashboard-stat-icon-purple" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                            </span>
                            <div><span>Order Menunggu</span><strong>{{ number_format($pendingOrderCount, 0, ',', '.') }}</strong></div>
                        </article>
                    @endif
                    <article class="dashboard-stat-card">
                        <span class="dashboard-stat-icon dashboard-stat-icon-pink" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 3 3 20h18L12 3zM12 9v5m0 3h.01"/></svg>
                        </span>
                        <div><span>Produk Stok Rendah</span><strong>{{ number_format($lowStockCount, 0, ',', '.') }}</strong></div>
                    </article>
                </section>

                <section class="dashboard-shortcuts" aria-labelledby="dashboard-shortcuts-title">
                    <div class="dashboard-section-heading">
                        <div>
                            <p class="products-eyebrow">AKSES CEPAT</p>
                            <h2 id="dashboard-shortcuts-title">Menu utama</h2>
                        </div>
                    </div>
                    <div class="dashboard-shortcut-grid">
                        @can('manage-employees')
                            <a class="dashboard-shortcut-card" href="{{ route('employees.index') }}">
                                <span class="dashboard-shortcut-icon dashboard-shortcut-icon-purple" aria-hidden="true">👥</span>
                                <strong>Data Karyawan</strong>
                                <span>Lihat akun, peran, dan jadwal karyawan.</span>
                                <b>Buka menu <span aria-hidden="true">→</span></b>
                            </a>
                        @endcan
                        @can('manage-products')
                            <a class="dashboard-shortcut-card" href="{{ route('products.index') }}">
                                <span class="dashboard-shortcut-icon dashboard-shortcut-icon-pink" aria-hidden="true">🍡</span>
                                <strong>Produk</strong>
                                <span>Kelola produk dan kategori toko.</span>
                                <b>Buka menu <span aria-hidden="true">→</span></b>
                            </a>
                        @endcan
                        @can('manage-stocks')
                            <a class="dashboard-shortcut-card" href="{{ route('stocks.index') }}">
                                <span class="dashboard-shortcut-icon dashboard-shortcut-icon-green" aria-hidden="true">📦</span>
                                <strong>Kelola Stok</strong>
                                <span>Catat stok masuk dan pantau persediaan.</span>
                                <b>Buka menu <span aria-hidden="true">→</span></b>
                            </a>
                        @endcan
                        @can('cashier')
                            <a class="dashboard-shortcut-card" href="{{ route('customer-orders.cashier.index') }}">
                                <span class="dashboard-shortcut-icon dashboard-shortcut-icon-orange" aria-hidden="true">🛎️</span>
                                <strong>Order Pelanggan</strong>
                                <span>Periksa dan proses pesanan pelanggan.</span>
                                <b>Buka menu <span aria-hidden="true">→</span></b>
                            </a>
                        @endcan
                        <a class="dashboard-shortcut-card" href="{{ route('sales.index') }}">
                            <span class="dashboard-shortcut-icon dashboard-shortcut-icon-purple" aria-hidden="true">🧾</span>
                            <strong>{{ $isCashier ? 'Transaksi Kasir' : 'Laporan Penjualan' }}</strong>
                            <span>{{ $isCashier ? 'Buat transaksi dan lihat riwayat penjualan.' : 'Pantau transaksi dan omzet penjualan.' }}</span>
                            <b>Buka menu <span aria-hidden="true">→</span></b>
                        </a>
                        <a class="dashboard-shortcut-card" href="{{ route('profile.show') }}">
                            <span class="dashboard-shortcut-icon dashboard-shortcut-icon-green" aria-hidden="true">👤</span>
                            <strong>Profil Saya</strong>
                            <span>Lihat dan perbarui informasi akun.</span>
                            <b>Buka menu <span aria-hidden="true">→</span></b>
                        </a>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
