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

        @php
            $categories = $products->pluck('category')->filter()->unique('id_kategori')->sortBy('nama_kategori');
        @endphp
        <div class="customer-order-toolbar">
            <div class="customer-order-tabs" role="tablist" aria-label="Kategori produk">
                <button type="button" class="customer-order-tab is-active" data-category-tab="">Semua</button>
                @foreach ($categories as $category)
                    <button type="button" class="customer-order-tab" data-category-tab="{{ $category->id_kategori }}">{{ $category->nama_kategori }}</button>
                @endforeach
            </div>
            <input type="search" class="customer-order-search" id="customer-product-search" placeholder="Cari produk..." autocomplete="off" aria-label="Cari produk">
        </div>
        <p class="customer-order-search-empty" id="customer-search-empty" hidden>Produk tidak ditemukan.</p>

        @if ($errors->any())
            <div class="action-notice action-notice-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('customer-orders.store') }}" method="POST" data-customer-order-form>
            @csrf
            <section class="customer-order-products" aria-label="Daftar produk">
                @forelse ($products as $product)
                    <article class="customer-order-product"
                        data-category="{{ $product->id_kategori }}"
                        data-search="{{ \Illuminate\Support\Str::lower($product->nama_produk.' '.$product->category?->nama_kategori) }}">
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
                        <div class="customer-order-quantity" data-qty-control>
    <button type="button" class="customer-order-add" data-qty-add>+ Tambah</button>
    <div class="customer-order-stepper" hidden>
        <button type="button" data-qty-minus aria-label="Kurangi">−</button>
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
        <button type="button" data-qty-plus aria-label="Tambah">+</button>
    </div>
</div>
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

    <script>
        (function () {
            const input = document.getElementById('customer-product-search');
            const emptyNotice = document.getElementById('customer-search-empty');
            const cards = document.querySelectorAll('.customer-order-product');
            const tabs = document.querySelectorAll('[data-category-tab]');
            let activeCategory = '';

            function applyFilter() {
                const keyword = input.value.trim().toLowerCase();
                let visible = 0;
                cards.forEach(function (card) {
                    const matchCategory = activeCategory === '' || card.dataset.category === activeCategory;
                    const matchSearch = (card.dataset.search || '').includes(keyword);
                    card.hidden = !(matchCategory && matchSearch);
                    if (!card.hidden) visible++;
                });
                emptyNotice.hidden = visible !== 0 || cards.length === 0;
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    activeCategory = tab.dataset.categoryTab;
                    tabs.forEach(function (t) { t.classList.toggle('is-active', t === tab); });
                    applyFilter();
                });
            });
            input.addEventListener('input', applyFilter);

            document.querySelectorAll('[data-qty-control]').forEach(function (box) {
    const addBtn = box.querySelector('[data-qty-add]');
    const stepper = box.querySelector('.customer-order-stepper');
    const input = box.querySelector('input');
    const minus = box.querySelector('[data-qty-minus]');
    const plus = box.querySelector('[data-qty-plus]');
    const max = parseInt(input.max, 10) || 0;

    function sync() {
        const qty = parseInt(input.value, 10) || 0;
        addBtn.hidden = qty > 0;
        stepper.hidden = qty === 0;
        plus.disabled = qty >= max;
    }

    function setQty(value) {
        input.value = Math.min(Math.max(value, 0), max);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        sync();
    }

    addBtn.addEventListener('click', function () { setQty(1); });
    minus.addEventListener('click', function () { setQty((parseInt(input.value, 10) || 0) - 1); });
    plus.addEventListener('click', function () { setQty((parseInt(input.value, 10) || 0) + 1); });
    input.addEventListener('change', function () { setQty(parseInt(input.value, 10) || 0); });
});

        })();
    </script>
</body>
</html>