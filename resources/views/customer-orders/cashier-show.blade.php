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
            <a class="sidebar-brand" href="{{ route('sales.index') }}" aria-label="Mochi Madiun">
                <span class="products-brand-mark" aria-hidden="true">M</span>
                <span class="sidebar-brand-name">MOCHI <strong>MADIUN</strong></span>
            </a>
            <p class="sidebar-section-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
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
                        <div><span>Order #{{ $customerOrder->id }}</span><time>{{ \Illuminate\Support\Carbon::parse($customerOrder->created_at)->locale('id')->translatedFormat('d F Y, H.i') }}</time></div>
                        <span class="customer-order-status-pill customer-order-status-{{ \Illuminate\Support\Str::slug($customerOrder->status) }}">{{ $customerOrder->status }}</span>
                    </div>
                    <dl class="customer-order-customer-info">
                        <div><dt>Nama pelanggan</dt><dd>{{ $customerOrder->nama_pelanggan }}</dd></div>
                        @if ($customerOrder->no_telepon)
                            <div><dt>Nomor telepon</dt><dd>{{ $customerOrder->no_telepon }}</dd></div>
                        @endif
                    </dl>
                    <div class="customer-order-track-items">
                        @foreach ($items as $item)
                            <div><span>{{ $item->nama_produk }} × {{ number_format((int) $item->jumlah, 0, ',', '.') }}</span><strong>Rp{{ number_format((int) $item->sub_total, 0, ',', '.') }}</strong></div>
                        @endforeach
                    </div>
                    <div class="customer-order-total"><span>Total pesanan</span><strong>Rp{{ number_format((int) $customerOrder->total, 0, ',', '.') }}</strong></div>

                    @if ($customerOrder->status === 'Menunggu')
                        <div class="customer-order-decision-actions">
                            <form action="{{ route('customer-orders.cashier.status', $customerOrder->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="Ditolak">
                                <button class="customer-order-reject-button" type="submit">Tolak pesanan</button>
                            </form>
                            <form action="{{ route('customer-orders.cashier.status', $customerOrder->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="Diproses">
                                <button class="sales-checkout-button" type="submit">Terima pesanan</button>
                            </form>
                        </div>
                    @elseif ($customerOrder->status === 'Diproses')
                        <form class="customer-order-reject-form" action="{{ route('customer-orders.cashier.status', $customerOrder->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="Ditolak">
                            <button class="customer-order-reject-button" type="submit">Batalkan order ini</button>
                        </form>
                        <form class="customer-order-payment-form" action="{{ route('customer-orders.cashier.complete', $customerOrder->id) }}" method="POST">
                            @csrf
                            <h2>Selesaikan pesanan & pembayaran</h2>
                            <label for="order-payment-method">Metode pembayaran</label>
                            <select id="order-payment-method" name="metode" required>
                                <option value="Tunai">Tunai</option>
                                <option value="Transfer">Transfer</option>
                                <option value="QRIS">QRIS</option>
                            </select>
                            <label for="order-amount-paid">Jumlah dibayar (Rp)</label>
                            <input id="order-amount-paid" name="jumlah_bayar" type="number" min="{{ $customerOrder->total }}" max="2147483647" step="1" value="{{ $customerOrder->total }}" required>
                            <button class="sales-checkout-button" type="submit">Tandai lunas & kurangi stok</button>
                            <p class="sales-checkout-hint">Stok berkurang dan penjualan tercatat setelah pembayaran berhasil.</p>
                        </form>
                    @elseif ($customerOrder->status === 'Selesai' && $customerOrder->sale_id)
                        <a class="sales-detail-link sales-history-action" href="{{ route('sales.show', $customerOrder->sale_id) }}">Lihat struk transaksi →</a>
                    @endif
                </section>
            </main>
        </div>
    </div>
</body>
</html>
