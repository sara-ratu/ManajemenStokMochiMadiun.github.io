<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Order Pelanggan | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page catalog-page">
    <header class="catalog-topbar">
        <a class="catalog-brand" href="{{ route('dashboard') }}" aria-label="Beranda Mochi Madiun">
            <img class="mochi-logo" src="{{ asset('images/mochi-madiun-logo.png') }}" alt="">
            @php($orderNow = now('Asia/Jakarta'))
            <small><time data-live-clock datetime="{{ $orderNow->toIso8601String() }}">{{ $orderNow->locale('id')->translatedFormat('l, d F Y') }} · {{ $orderNow->format('H.i') }}</time></small>
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
                    <span>Transaksi</span>
                </a>
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('customer-orders.cashier.index') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/><path d="m8 19 4 3 4-3"/></svg>
                    <span>Order</span><span class="customer-order-nav-count" data-pending-count>{{ $orders->count() }}</span>
                </a>
                <a class="sidebar-nav-link" href="{{ route('stocks.index') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4z"/><path d="M4 7l2-4h12l2 4M12 11v6m-3-3h6"/></svg>
                    <span>Kelola Stok</span>
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
                <div class="topbar-page-label">Order Pelanggan</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ auth()->user()->nama_lengkap ?: 'Kasir' }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">@csrf<button class="logout-button" type="submit">Keluar</button></form>
                </div>
            </header>
            <main class="products-main stock-main customer-orders-cashier-main" data-cashier-order-poll data-notifications-url="{{ route('customer-orders.notifications') }}" data-latest-order-id="{{ $latestOrderId }}">
                @if (session('status'))
                    <div class="action-notice action-notice-success" role="status">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="action-notice action-notice-error" role="alert">{{ session('error') }}</div>
                @endif
                <section class="products-heading stock-heading">
                    <div>
                        <p class="products-eyebrow">ANTREAN ORDER</p>
                        <h1>Order Pelanggan</h1>
                        <p class="products-subtitle">Periksa order baru dan konfirmasi pembayaran. Daftar diperbarui setiap 8 detik.</p>
                    </div>
                    <span class="customer-order-pending-summary"><strong data-pending-count>{{ $orders->count() }}</strong> menunggu</span>
                </section>
                <div class="customer-order-new-alert" role="status" data-new-order-alert hidden>
                    Ada order baru. <button type="button" data-refresh-order-queue>Muat ulang antrean</button>
                </div>
                <section class="customer-order-queue">
                    @forelse ($orders as $order)
                        <article class="customer-order-queue-card">
                            <div class="customer-order-queue-heading">
                                <div><span>Order #{{ $order->id_pesanan }}</span><time>{{ \Illuminate\Support\Carbon::parse($order->tanggal)->locale('id')->translatedFormat('d M Y, H.i') }}</time></div>
                                <span class="customer-order-status-pill customer-order-status-{{ \Illuminate\Support\Str::slug($order->status) }}">Menunggu</span>
                            </div>
                            <p class="customer-order-queue-customer">Pelanggan <span>· Order daring</span></p>
                            <p class="customer-order-queue-items">{{ $order->items->map(fn ($item) => ($item->nama_produk ?: 'Produk #'.$item->id_produk).' × '.$item->jumlah)->join(', ') }}</p>
                            <div class="customer-order-queue-bottom">
                                <strong>Rp{{ number_format((int) $order->total, 0, ',', '.') }}</strong>
                                <a class="sales-detail-link sales-history-action" href="{{ route('customer-orders.cashier.show', $order->id_pesanan) }}">Periksa pesanan <span aria-hidden="true">→</span></a>
                            </div>
                        </article>
                    @empty
                        <div class="customer-order-empty">
                            <span aria-hidden="true">🔔</span>
                            <strong>Belum ada pesanan aktif</strong>
                            <p>Order yang masuk akan muncul di sini.</p>
                        </div>
                    @endforelse
                </section>
            </main>
        </div>
    </div>
</body>
</html>
