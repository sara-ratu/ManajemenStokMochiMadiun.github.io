<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Order Pelanggan | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page">
    <div class="products-app-shell">
        <aside class="app-sidebar">
            <a class="sidebar-brand" href="{{ route('dashboard') }}" aria-label="Mochi Madiun">
                <span class="products-brand-mark" aria-hidden="true">M</span>
                <span class="sidebar-brand-name">MOCHI <strong>MADIUN</strong></span>
            </a>
            <p class="sidebar-section-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <a class="sidebar-nav-link" href="{{ route('dashboard') }}"><span>Dashboard</span></a>
                <a class="sidebar-nav-link" href="{{ route('sales.index') }}"><span>Transaksi</span></a>
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('customer-orders.cashier.index') }}" aria-current="page"><span>Order Pelanggan</span><span class="customer-order-nav-count" data-pending-count>{{ $orders->count() }}</span></a>
                <a class="sidebar-nav-link" href="{{ route('stocks.index') }}"><span>Kelola Stok</span></a>
                <a class="sidebar-nav-link" href="{{ route('profile.show') }}"><span>Profil</span></a>
            </nav>
            <div class="sidebar-footer">Mochi Madiun <span>Manajemen Toko</span></div>
        </aside>
        <div class="products-workspace">
            <header class="products-topbar">
                <div class="topbar-page-label">Order Pelanggan</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ auth()->user()->nama_lengkap ?: 'Kasir' }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">@csrf<button class="logout-button" type="submit">Keluar</button></form>
                </div>
            </header>
            <main class="products-main customer-orders-cashier-main" data-cashier-order-poll data-notifications-url="{{ route('customer-orders.notifications') }}" data-latest-order-id="{{ $latestOrderId }}">
                @if (session('status'))
                    <div class="action-notice action-notice-success" role="status">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="action-notice action-notice-error" role="alert">{{ session('error') }}</div>
                @endif
                <section class="products-heading">
                    <div>
                        <p class="products-eyebrow">ANTREAN PESANAN</p>
                        <h1>Order dari Pelanggan</h1>
                        <p class="products-subtitle">Pesanan baru diperiksa di sini. Halaman memeriksa order baru setiap 8 detik.</p>
                    </div>
                    <span class="customer-order-pending-summary"><strong data-pending-count>{{ $orders->count() }}</strong> menunggu</span>
                </section>
                <div class="customer-order-new-alert" role="status" data-new-order-alert hidden>
                    Ada order pelanggan baru. <button type="button" data-refresh-order-queue>Muat ulang antrean</button>
                </div>
                <section class="customer-order-queue">
                    @forelse ($orders as $order)
                        <article class="customer-order-queue-card">
                            <div class="customer-order-queue-heading">
                                <div><span>Order #{{ $order->id_pesanan }}</span><time>{{ \Illuminate\Support\Carbon::parse($order->tanggal)->locale('id')->translatedFormat('d M Y, H.i') }}</time></div>
                                <span class="customer-order-status-pill customer-order-status-{{ \Illuminate\Support\Str::slug($order->status) }}">Menunggu</span>
                            </div>
                            <p class="customer-order-queue-customer">Pelanggan <span>· Order online</span></p>
                            <p class="customer-order-queue-items">{{ $order->items->map(fn ($item) => ($item->nama_produk ?: 'Produk #'.$item->id_produk).' × '.$item->jumlah)->join(', ') }}</p>
                            <div class="customer-order-queue-bottom">
                                <strong>Rp{{ number_format((int) $order->total, 0, ',', '.') }}</strong>
                                <a class="sales-detail-link sales-history-action" href="{{ route('customer-orders.cashier.show', $order->id_pesanan) }}">Periksa order <span aria-hidden="true">→</span></a>
                            </div>
                        </article>
                    @empty
                        <div class="customer-order-empty">
                            <span aria-hidden="true">🔔</span>
                            <strong>Belum ada order aktif</strong>
                            <p>Pesanan pelanggan yang masuk akan muncul di sini.</p>
                        </div>
                    @endforelse
                </section>
            </main>
        </div>
    </div>
</body>
</html>
