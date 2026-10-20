<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Status Pesanan | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page customer-order-page">
    <header class="catalog-topbar">
        <a class="catalog-brand" href="{{ route('customer-orders.create') }}" aria-label="Mochi Madiun">
            <span>MOCHI MADIUN</span>
            <small>Status pesanan pelanggan</small>
        </a>
    </header>
    <main class="products-main customer-order-main customer-order-track">
        <section class="customer-order-status-card" data-customer-order-status data-status-url="{{ route('customer-orders.status', $order->tracking_token) }}">
            <p class="products-eyebrow">PESANAN #{{ $order->id }}</p>
            <h1>Terima kasih, {{ $order->nama_pelanggan }}!</h1>
            <p>Pesananmu sudah dikirim ke kasir. Halaman ini memperbarui status secara otomatis.</p>
            <div class="customer-order-status-pill" data-status-label>{{ $order->status }}</div>
            <p class="customer-order-status-message" data-status-message>
                @if ($order->status === 'Menunggu')
                    Menunggu konfirmasi dari kasir.
                @elseif ($order->status === 'Diproses')
                    Pesanan diterima. Silakan lakukan pembayaran kepada kasir.
                @elseif ($order->status === 'Ditolak')
                    Maaf, kasir belum dapat menerima pesanan ini. Silakan hubungi kasir.
                @else
                    Pesanan selesai. Terima kasih sudah berbelanja!
                @endif
            </p>
            <div class="customer-order-track-items">
                @foreach ($items as $item)
                    <div><span>{{ $item->nama_produk }} × {{ $item->jumlah }}</span><strong>Rp{{ number_format((int) $item->sub_total, 0, ',', '.') }}</strong></div>
                @endforeach
            </div>
            <div class="customer-order-total"><span>Total pesanan</span><strong>Rp{{ number_format((int) $order->total, 0, ',', '.') }}</strong></div>
            <a class="customer-order-new-link" href="{{ route('customer-orders.create') }}">Kembali ke menu</a>
        </section>
    </main>
</body>
</html>
