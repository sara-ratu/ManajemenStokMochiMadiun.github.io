<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Order #{{ $customerOrder->id_pesanan }} | Mochi Madiun</title>
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
                <a class="sidebar-nav-link" href="{{ route('dashboard') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z"/></svg><span>Beranda</span></a>
                <a class="sidebar-nav-link" href="{{ route('sales.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h3m2 0h3m-8 4h8"/></svg><span>Transaksi</span></a>
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('customer-orders.cashier.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/><path d="m8 19 4 3 4-3"/></svg><span>Order</span></a>
                <a class="sidebar-nav-link" href="{{ route('stocks.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4z"/><path d="M4 7l2-4h12l2 4M12 11v6m-3-3h6"/></svg><span>Kelola Stok</span></a>
                <a class="sidebar-nav-link" href="{{ route('profile.show') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><span>Profil</span></a>
            </nav>
            <div class="sidebar-footer">Mochi Madiun <span>Manajemen Toko</span></div>
        </aside>
        <div class="products-workspace">
            <header class="products-topbar">
                <div class="topbar-page-label">Detail Order</div>
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
                            <span class="customer-order-status-pill customer-order-status-{{ \Illuminate\Support\Str::slug($customerOrder->status) }}">{{ $customerOrder->status === 'Pending' ? 'Menunggu' : $customerOrder->status }}</span>
                        </div>
                        <div class="customer-order-track-items">
                            @foreach ($items as $item)
                                <div><span>{{ $item->nama_produk ?: 'Produk #'.$item->id_produk }} × {{ number_format((int) $item->jumlah, 0, ',', '.') }}</span><strong>Rp{{ number_format((int) $item->sub_total, 0, ',', '.') }}</strong></div>
                            @endforeach
                        </div>
                        <div class="customer-order-total"><span>Total order</span><strong>Rp{{ number_format((int) $customerOrder->total, 0, ',', '.') }}</strong></div>

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
