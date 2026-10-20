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
        <section class="customer-order-status-card" data-customer-order-status data-status-url="{{ $statusUrl }}">
            <p class="products-eyebrow">PESANAN #{{ $customerOrder->id_pesanan }}</p>
            <h1>Pesanan berhasil dikirim</h1>
            <p>Kasir akan memeriksa pesananmu. Halaman ini memperbarui status secara otomatis.</p>
            <div class="customer-order-status-pill" data-status-label>{{ $customerOrder->status }}</div>
            <p class="customer-order-status-message" data-status-message>
                @if ($customerOrder->status === 'Pending')
                    Menunggu konfirmasi dari kasir.
                @elseif ($customerOrder->status === 'Batal')
                    Pesanan dibatalkan oleh kasir. Silakan hubungi kasir jika perlu.
                @elseif ($customerOrder->status === 'Selesai')
                    Pesanan selesai. Terima kasih sudah berbelanja!
                @endif
            </p>
            <div class="customer-order-track-items">
                @foreach ($items as $item)
                    <div><span>{{ $item->nama_produk ?: 'Produk #'.$item->id_produk }} × {{ $item->jumlah }}</span><strong>Rp{{ number_format((int) $item->sub_total, 0, ',', '.') }}</strong></div>
                @endforeach
            </div>
            <div class="customer-order-total"><span>Total pesanan</span><strong>Rp{{ number_format((int) $customerOrder->total, 0, ',', '.') }}</strong></div>
            <a class="customer-order-new-link" href="{{ route('customer-orders.create') }}">Kembali ke menu</a>
        </section>
    </main>
</body>
</html>
