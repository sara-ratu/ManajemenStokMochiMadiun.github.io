<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Pesan Mochi | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page customer-order-page">
    <header class="catalog-topbar">
        <a class="catalog-brand" href="{{ route('customer-orders.create') }}" aria-label="Mochi Madiun">
            <span>MOCHI MADIUN</span>
            <small>Menu pemesanan pelanggan</small>
        </a>
    </header>
    <main class="products-main customer-order-main">
        <section class="products-heading">
            <div>
                <p class="products-eyebrow">PESAN ONLINE</p>
                <h1>Pilih Mochi Favoritmu</h1>
                <p class="products-subtitle">Kirim pesanan ke kasir. Pembayaran dilakukan langsung kepada kasir setelah pesanan diperiksa.</p>
            </div>
        </section>

        @if ($errors->any())
            <div class="action-notice action-notice-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('customer-orders.store') }}" method="POST" data-customer-order-form>
            @csrf
            <section class="customer-order-products" aria-label="Daftar produk">
                @forelse ($products as $product)
                    <article class="customer-order-product">
                        <div class="customer-order-product-image">
                            @if ($product->gambar)
                                <img
                                    src="{{ \Illuminate\Support\Str::startsWith($product->gambar, ['http://', 'https://']) ? $product->gambar : \Illuminate\Support\Facades\Storage::disk('public')->url($product->gambar) }}"
                                    alt="{{ $product->nama_produk ?: 'Produk mochi' }}"
                                    loading="lazy"
                                >
                            @else
                                <span aria-hidden="true">🍡</span>
                            @endif
                        </div>
                        <div class="customer-order-product-info">
                            <span>{{ $product->category?->nama_kategori ?: 'Mochi Madiun' }}</span>
                            <strong>{{ $product->nama_produk ?: 'Produk tanpa nama' }}</strong>
                            <b>Rp{{ number_format((int) $product->harga, 0, ',', '.') }}</b>
                            <small>{{ number_format(max((int) $product->stok, 0), 0, ',', '.') }} tersedia</small>
                        </div>
                        <label class="customer-order-quantity">
                            <span>Jumlah</span>
                            <input
                                type="number"
                                min="0"
                                max="{{ min(max((int) $product->stok, 0), 100) }}"
                                value="0"
                                inputmode="numeric"
                                data-customer-product-quantity
                                data-product-id="{{ $product->id_produk }}"
                                data-product-name="{{ $product->nama_produk ?: 'Produk tanpa nama' }}"
                                data-product-price="{{ (int) $product->harga }}"
                            >
                        </label>
                    </article>
                @empty
                    <div class="customer-order-empty">
                        <span aria-hidden="true">🍡</span>
                        <strong>Produk belum tersedia</strong>
                        <p>Silakan coba kembali beberapa saat lagi.</p>
                    </div>
                @endforelse
            </section>

            <section class="customer-order-submit-card">
                <div class="customer-order-total"><span>Perkiraan total</span><strong data-customer-order-total>Rp0</strong></div>
                <div data-customer-order-items></div>
                <button class="sales-checkout-button" type="submit" data-customer-order-submit disabled>Kirim Pesanan ke Kasir</button>
                <p class="sales-checkout-hint">Stok belum dipotong. Simpan tautan nomor pesanan untuk memantau statusnya.</p>
            </section>
        </form>
    </main>
</body>
</html>
