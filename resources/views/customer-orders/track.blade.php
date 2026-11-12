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
            <img class="mochi-logo" src="{{ asset('images/logo-mochi-madiun.png') }}" alt="">
            <small>Status pesanan pelanggan</small>
        </a>
    </header>
    <main class="products-main customer-order-main customer-order-track">
        <section class="customer-order-status-card" data-customer-order-status data-status-url="{{ $statusUrl }}">
            <div class="customer-order-track-heading">
                <p class="products-eyebrow">PESANAN #{{ $customerOrder->id_pesanan }}</p>
                <div class="customer-order-status-pill customer-order-status-{{ strtolower($customerOrder->status) }}" data-status-label>{{ $customerOrder->status === 'Pending' ? 'Menunggu' : $customerOrder->status }}</div>
            </div>
            <h1 data-status-heading>
                @if ($customerOrder->status === 'Batal')
                    Pesanan dibatalkan
                @elseif ($customerOrder->status === 'Selesai')
                    Pesanan selesai
                @else
                    Pesanan berhasil dikirim
                @endif
            </h1>
            <p class="customer-order-status-message" data-status-message>
                @if ($customerOrder->status === 'Pending')
                    Setelah membayar, kasir akan memeriksa pesananmu dan memperbarui statusnya. Halaman ini diperbarui otomatis.
                @elseif ($customerOrder->status === 'Batal')
                    Pesanan dibatalkan oleh kasir. Silakan hubungi kasir jika perlu.
                @elseif ($customerOrder->status === 'Selesai')
                    Pesanan selesai. Terima kasih sudah berbelanja!
                @endif
            </p>

            @if ($customerOrder->status === 'Pending')
                <section class="customer-order-next-steps" data-customer-order-next-steps aria-labelledby="customer-order-next-steps-title">
                    <h2 id="customer-order-next-steps-title">Langkah selanjutnya</h2>
                    <ol>
                        <li>Bayar <strong>Rp{{ number_format((int) $customerOrder->total, 0, ',', '.') }}</strong> lewat QRIS di bawah atau tunai langsung di kasir.</li>
                        <li>Tunjukkan nomor pesanan <strong>#{{ $customerOrder->id_pesanan }}</strong> kepada kasir.</li>
                        <li>Kasir akan memeriksa pembayaran dan mengonfirmasi pesananmu.</li>
                    </ol>
                </section>
            @endif

            <div class="customer-order-total customer-order-track-total">
                <span>Total pesanan</span>
                <strong>Rp{{ number_format((int) $customerOrder->total, 0, ',', '.') }}</strong>
                @if ($customerOrder->status === 'Pending')
                    <a class="customer-order-qris-link" href="#customer-order-qris">Bayar dengan QRIS <span aria-hidden="true">↓</span></a>
                @endif
            </div>

            @if ($customerOrder->status === 'Pending')
                <section class="customer-order-qris" id="customer-order-qris" data-customer-order-qris aria-labelledby="customer-order-qris-title">
                    <div class="customer-order-qris-copy">
                        <h2 id="customer-order-qris-title">Bayar dengan QRIS</h2>
                        <p>Pindai QRIS menggunakan aplikasi pembayaran, lalu masukkan nominal tepat berikut.</p>
                        <strong class="customer-order-qris-amount">Rp{{ number_format((int) $customerOrder->total, 0, ',', '.') }}</strong>
                    </div>
                    <img src="{{ asset('images/qris-merchant-sara-ratu.png') }}" alt="QRIS merchant Mochi Madiun">
                    <p class="customer-order-qris-warning">QRIS ini bersifat statis. Pembayaran belum terkonfirmasi otomatis; kasir akan memeriksa transaksi masuk. Simpan bukti pembayaran sampai pesanan dikonfirmasi.</p>
                </section>
            @endif

            <section class="customer-order-order-details" aria-labelledby="customer-order-details-title">
                <h2 id="customer-order-details-title">Rincian pesanan</h2>
                <div class="customer-order-track-items">
                    @foreach ($items as $item)
                        <div><span>{{ $item->nama_produk ?: 'Produk #'.$item->id_produk }} × {{ $item->jumlah }}</span><strong>Rp{{ number_format((int) $item->sub_total, 0, ',', '.') }}</strong></div>
                    @endforeach
                </div>
            </section>
            <a class="customer-order-new-link" href="{{ route('customer-orders.create') }}">Kembali ke menu</a>
        </section>
    </main>
</body>
</html>
