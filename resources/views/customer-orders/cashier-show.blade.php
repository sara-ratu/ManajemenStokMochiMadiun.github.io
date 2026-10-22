<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Order #{{ $customerOrder->id }} | Mochi Madiun</title>
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
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('customer-orders.cashier.index') }}"><span>Order Pelanggan</span></a>
                <a class="sidebar-nav-link" href="{{ route('stocks.index') }}"><span>Kelola Stok</span></a>
                <a class="sidebar-nav-link" href="{{ route('profile.show') }}"><span>Profil</span></a>
            </nav>
            <div class="sidebar-footer">Mochi Madiun <span>Manajemen Toko</span></div>
        </aside>
        <div class="products-workspace">
            <header class="products-topbar">
                <div class="topbar-page-label">Detail Order Pelanggan</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ auth()->user()->nama_lengkap ?: 'Kasir' }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">@csrf<button class="logout-button" type="submit">Keluar</button></form>
                </div>
            </header>
            <main class="products-main customer-order-detail-main">
                @if (session('status'))
                    <div class="action-notice action-notice-success" role="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="action-notice action-notice-error" role="alert">{{ $errors->first() }}</div>
                @endif
                <a class="customer-order-back-link" href="{{ route('customer-orders.cashier.index') }}">← Kembali ke antrean order</a>
                <section class="customer-order-detail-card">
                        <div class="customer-order-queue-heading">
                            <div><span>Order #{{ $customerOrder->id_pesanan }}</span><time>{{ \Illuminate\Support\Carbon::parse($customerOrder->tanggal)->locale('id')->translatedFormat('d F Y, H.i') }}</time></div>
                            <span class="customer-order-status-pill customer-order-status-{{ \Illuminate\Support\Str::slug($customerOrder->status) }}">{{ $customerOrder->status }}</span>
                        </div>
                        <div class="customer-order-track-items">
                            @foreach ($items as $item)
                                <div><span>{{ $item->nama_produk ?: 'Produk #'.$item->id_produk }} × {{ number_format((int) $item->jumlah, 0, ',', '.') }}</span><strong>Rp{{ number_format((int) $item->sub_total, 0, ',', '.') }}</strong></div>
                            @endforeach
                        </div>
                        <div class="customer-order-total"><span>Total pesanan</span><strong>Rp{{ number_format((int) $customerOrder->total, 0, ',', '.') }}</strong></div>

                        @if ($customerOrder->status === 'Pending')
                            <div class="customer-order-decision-actions">
                                <form action="{{ route('customer-orders.cashier.reject', $customerOrder->id_pesanan) }}" method="POST">
                                    @csrf
                                    <button class="customer-order-reject-button" type="submit">Tolak pesanan</button>
                                </form>
                            </div>
                            <form class="customer-order-payment-form" action="{{ route('customer-orders.cashier.complete', $customerOrder->id_pesanan) }}" method="POST">
                                @csrf
                                <h2>Proses pembayaran</h2>
                            <label for="order-payment-method">Metode pembayaran</label>
                            <select id="order-payment-method" name="metode" required>
                                <option value="Tunai">Tunai</option>
                                <option value="Transfer">Transfer</option>
                                <option value="QRIS">QRIS</option>
                            </select>
                            <label for="order-amount-paid">Jumlah dibayar (Rp)</label>
                            <input id="order-amount-paid" name="jumlah_bayar" type="number" min="{{ $customerOrder->total }}" max="2147483647" step="1" value="{{ $customerOrder->total }}" required>
                            <button class="sales-checkout-button" type="submit">Selesaikan transaksi</button>
                            <p class="sales-checkout-hint">Stok berkurang dan penjualan tercatat setelah pembayaran berhasil.</p>
                        </form>
                    @elseif ($customerOrder->status === 'Selesai')
                        <a class="sales-detail-link sales-history-action" href="{{ route('sales.show', $customerOrder->id_pesanan) }}">Lihat struk transaksi →</a>
                    @endif
                </section>
            </main>
        </div>
    </div>
</body>
</html>
