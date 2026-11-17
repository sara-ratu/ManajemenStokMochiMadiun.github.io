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
        <a class="catalog-brand" href="{{ route('dashboard') }}" aria-label="Beranda Mochi Madiun">
            <img class="mochi-logo" src="{{ asset('images/logo-mochi-madiun.png') }}" alt="">
            @php($receiptNow = now('Asia/Jakarta'))
            <small class="catalog-datetime"><time data-live-clock datetime="{{ $receiptNow->toIso8601String() }}">{{ $receiptNow->locale('id')->translatedFormat('D, d M Y') }} · {{ $receiptNow->format('H.i') }} WIB</time></small>
        </a>
        <a class="sales-receipt-top-link" href="{{ route('sales.index') }}">{{ auth()->user()->can('cashier') ? 'Kembali ke kasir' : 'Kembali ke laporan' }}</a>
    </header>
    <div class="products-app-shell">
        <aside class="app-sidebar">
            <p class="sidebar-section-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <a class="sidebar-nav-link" href="{{ route('dashboard') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z"/></svg><span>Beranda</span></a>
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('sales.index') }}" aria-current="page"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h3m2 0h3m-8 4h8"/></svg><span>{{ auth()->user()->can('cashier') ? 'Transaksi' : 'Laporan Penjualan' }}</span></a>
                @can('cashier')
                    <a class="sidebar-nav-link" href="{{ route('history.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9 9 0 0 0-9 9zm0 0h7m-7 0 3-3m-3 3 3 3M12 3v7m0 0 3 3m-3-3-3 3"/></svg><span>Riwayat</span></a>
                    <a class="sidebar-nav-link" href="{{ route('customer-orders.cashier.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/><path d="m8 19 4 3 4-3"/></svg><span>Order</span></a>
                @endcan
                @can('manage-employees')
                    <a class="sidebar-nav-link" href="{{ route('employees.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 4a4 4 0 0 1 0 8m2 2a7 7 0 0 1 4 7"/></svg><span>Data Karyawan</span></a>
                @endcan
                @can('manage-products')
                    <a class="sidebar-nav-link" href="{{ route('products.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4.5 7.5 7.5 4 7.5-4M12 12v9"/></svg><span>Produk</span></a>
                @endcan
                @can('manage-stocks')
                    <a class="sidebar-nav-link" href="{{ route('stocks.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4z"/><path d="M4 7l2-4h12l2 4M12 11v6m-3-3h6"/></svg><span>Kelola Stok</span></a>
                @endcan
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
                @php($receiptDate = \Illuminate\Support\Carbon::parse($order->tanggal)->timezone('Asia/Jakarta'))
                @php($receiptQuantity = $details->sum('jumlah'))
                @php($receiptSubtotal = $details->sum('sub_total'))
                <section class="sales-receipt-card">
                    <div class="sales-receipt-print-brand">
                        <img class="receipt-mochi-logo" src="{{ asset('images/logo-mochi-madiun.png') }}" alt="Logo Mochi Madiun">
                        <strong>Mochi Madiun</strong>
                        <span class="sales-receipt-address">Perumahan Widodo Kencono 1 blok B3, Pandean, Kec. Taman, Kota Madiun, Jawa Timur 63133</span>
                        <span class="sales-receipt-phone">Telp. 0895-0893-7076</span>
                    </div>
                    <div class="sales-receipt-heading">
                        <span class="sales-receipt-icon" aria-hidden="true">✓</span>
                        <p class="products-eyebrow">TRANSAKSI BERHASIL</p>
                        <h1>Terima kasih!</h1>
                        <p>Pesanan <strong>#{{ $order->id_pesanan }}</strong> berhasil disimpan.</p>
                    </div>
                    <dl class="sales-receipt-meta">
                        <div><dt>{{ $receiptDate->locale('id')->translatedFormat('d M Y') }}</dt><dd>Kasir</dd></div>
                        <div><dt>{{ $receiptDate->format('H.i') }}</dt><dd>No. #{{ $order->id_pesanan }}</dd></div>
                        <div><dt>Status: {{ $order->status }}</dt><dd>{{ $payment->metode ?? '-' }}</dd></div>
                    </dl>
                    <div class="sales-receipt-items">
                        @foreach ($details as $detail)
                            <div class="sales-receipt-item">
                                <div class="sales-receipt-item-main">
                                    <strong>{{ $loop->iteration }}. {{ $detail->nama_produk ?: 'Produk #'.$detail->id_produk }}</strong>
                                    <strong>Rp {{ number_format((int) $detail->sub_total, 0, ',', '.') }}</strong>
                                </div>
                                <div class="sales-receipt-item-detail">
                                    <span>{{ number_format((int) $detail->jumlah, 0, ',', '.') }} x {{ number_format((int) $detail->harga, 0, ',', '.') }}</span>
                                    <span>Rp {{ number_format((int) $detail->sub_total, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="sales-receipt-summary">
                        <div class="sales-receipt-summary-row"><span>Total QTY : {{ number_format((int) $receiptQuantity, 0, ',', '.') }}</span></div>
                        <div class="sales-receipt-summary-row"><span>Sub Total</span><span>Rp {{ number_format((int) $receiptSubtotal, 0, ',', '.') }}</span></div>
                        <div class="sales-receipt-summary-row sales-receipt-grand-total"><strong>Total</strong><strong>Rp {{ number_format((int) $order->total, 0, ',', '.') }}</strong></div>
                        @if ($payment)
                            <div class="sales-receipt-summary-row"><span>Bayar ({{ $payment->metode }})</span><span>Rp {{ number_format((int) $payment->jumlah, 0, ',', '.') }}</span></div>
                            <div class="sales-receipt-summary-row"><span>Kembali</span><span>Rp {{ number_format(max((int) $payment->jumlah - (int) $order->total, 0), 0, ',', '.') }}</span></div>
                        @endif
                    </div>
                    <div class="sales-receipt-thanks">Terima kasih telah berbelanja</div>
                    <div class="sales-receipt-actions">
                        <a class="sales-detail-link" href="{{ route('sales.index') }}">Kembali ke kasir</a>
                        <label class="sales-receipt-width-control">
                            <span>Lebar kertas</span>
                            <select data-receipt-width aria-label="Lebar kertas struk">
                                <option value="58">58 mm</option>
                                <option value="80">80 mm</option>
                            </select>
                        </label>
                        <button class="sales-checkout-button sales-print-button" type="button" data-receipt-print>Cetak Struk</button>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
