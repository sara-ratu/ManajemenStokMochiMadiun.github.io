<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Struk #{{ $order->id_pesanan }} | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page catalog-page">
    <header class="catalog-topbar">
        <a class="catalog-brand" href="{{ route(auth()->user()->can('manage-products') ? 'products.index' : 'sales.index') }}" aria-label="Mochi Madiun">
            <span>MOCHI MADIUN</span>
            @php($receiptNow = now('Asia/Jakarta'))
            <small><time data-live-clock datetime="{{ $receiptNow->toIso8601String() }}">{{ $receiptNow->locale('id')->translatedFormat('l, d F Y') }} · {{ $receiptNow->format('H.i') }}</time></small>
        </a>
        <a class="sales-receipt-top-link" href="{{ route('sales.index') }}">{{ auth()->user()->can('cashier') ? 'Kembali ke kasir' : 'Kembali ke laporan' }}</a>
    </header>
    <div class="products-app-shell">
        <aside class="app-sidebar">
            <p class="sidebar-section-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                @can('manage-employees')
                    <a class="sidebar-nav-link" href="{{ route('employees.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 4a4 4 0 0 1 0 8m2 2a7 7 0 0 1 4 7"/></svg><span>Data Karyawan</span></a>
                @endcan
                @can('manage-products')
                    <a class="sidebar-nav-link" href="{{ route('products.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4.5 7.5 7.5 4 7.5-4M12 12v9"/></svg><span>Produk</span></a>
                @endcan
                @can('manage-stocks')
                    <a class="sidebar-nav-link" href="{{ route('stocks.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4z"/><path d="M4 7l2-4h12l2 4M12 11v6m-3-3h6"/></svg><span>Kelola Stok</span></a>
                @endcan
                @can('cashier')
                    <a class="sidebar-nav-link" href="{{ route('customer-orders.cashier.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/><path d="m8 19 4 3 4-3"/></svg><span>Order Pelanggan</span></a>
                @endcan
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('sales.index') }}" aria-current="page"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h3m2 0h3m-8 4h8"/></svg><span>{{ auth()->user()->can('cashier') ? 'Transaksi' : 'Laporan Penjualan' }}</span></a>
                <a class="sidebar-nav-link" href="{{ route('profile.show') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><span>Profil</span></a>
            </nav>
            <div class="sidebar-footer">Mochi Madiun <span>Manajemen Toko</span></div>
        </aside>
        <div class="products-workspace">
            <header class="products-topbar">
                <div class="topbar-page-label">Detail Transaksi</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ auth()->user()->nama_lengkap ?: 'Pengguna' }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">@csrf<button class="logout-button" type="submit">Keluar</button></form>
                </div>
            </header>
            <main class="products-main sales-main">
                @if (session('status'))
                    <div class="action-notice action-notice-success" role="status">{{ session('status') }}</div>
                @endif
                <section class="sales-receipt-card">
                    <div class="sales-receipt-print-brand">
                        <strong>MOCHI MADIUN</strong>
                        <span>BUKTI TRANSAKSI</span>
                    </div>
                    <div class="sales-receipt-heading">
                        <span class="sales-receipt-icon" aria-hidden="true">✓</span>
                        <p class="products-eyebrow">TRANSAKSI BERHASIL</p>
                        <h1>Terima kasih!</h1>
                        <p>Pesanan <strong>#{{ $order->id_pesanan }}</strong> berhasil disimpan.</p>
                    </div>
                    <dl class="sales-receipt-meta">
                        <div class="sales-receipt-print-order"><dt>No. Pesanan</dt><dd>#{{ $order->id_pesanan }}</dd></div>
                        <div><dt>Tanggal</dt><dd>{{ \Illuminate\Support\Carbon::parse($order->tanggal)->locale('id')->translatedFormat('d F Y, H.i') }}</dd></div>
                        <div><dt>Kasir</dt><dd>{{ $order->nama_kasir ?: 'Pengguna #'.$order->ms_user_id_user }}</dd></div>
                        <div><dt>Status</dt><dd><span class="sale-status-badge">{{ $order->status }}</span></dd></div>
                        @if ($payment)
                            <div><dt>Metode Pembayaran</dt><dd>{{ $payment->metode }}</dd></div>
                        @endif
                    </dl>
                    <div class="catalog-table-scroll">
                        <table class="stock-table sales-receipt-table">
                            <thead><tr><th scope="col">Produk</th><th scope="col">Harga</th><th scope="col">Jumlah</th><th scope="col">Subtotal</th></tr></thead>
                            <tbody>
                                @foreach ($details as $detail)
                                    <tr>
                                        <td class="stock-product-cell">
                                            {{ $detail->nama_produk ?: 'Produk #'.$detail->id_produk }}
                                            <span class="sales-receipt-item-detail">Rp{{ number_format((int) $detail->harga, 0, ',', '.') }} × {{ number_format((int) $detail->jumlah, 0, ',', '.') }} pcs</span>
                                        </td>
                                        <td>Rp{{ number_format((int) $detail->harga, 0, ',', '.') }}</td>
                                        <td>{{ number_format((int) $detail->jumlah, 0, ',', '.') }} pcs</td>
                                        <td class="sales-history-total">Rp{{ number_format((int) $detail->sub_total, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="sales-receipt-total"><span>Total Dibayar</span><strong>Rp{{ number_format((int) $order->total, 0, ',', '.') }}</strong></div>
                    @if ($payment)
                        <div class="sales-receipt-payment">
                            <span>Jumlah diterima: Rp{{ number_format((int) $payment->jumlah, 0, ',', '.') }}</span>
                            <span>Kembalian: Rp{{ number_format(max((int) $payment->jumlah - (int) $order->total, 0), 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="sales-receipt-actions">
                        <a class="sales-detail-link" href="{{ route('sales.index') }}">Kembali ke kasir</a>
                        <button class="sales-checkout-button sales-print-button" type="button" onclick="window.print()">Cetak Struk</button>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
