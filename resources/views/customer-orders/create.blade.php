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
            <img class="mochi-logo" src="{{ asset('images/mochi-madiun-logo.png') }}" alt="">
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
            <div class="customer-order-layout">
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

                <aside class="customer-order-cart" aria-label="Keranjang pesanan">
                    <div class="customer-order-cart-heading">
                        <h2>Keranjang</h2>
                        <span><span data-cart-count>0</span> item</span>
                    </div>
                    <div class="customer-order-cart-list" data-cart-list></div>

                    <section class="customer-order-submit-card">
                        <div class="customer-order-total"><span>Perkiraan total</span><strong data-customer-order-total>Rp0</strong></div>
                        <div data-customer-order-items></div>
                        <button class="sales-checkout-button" type="submit" data-customer-order-submit disabled>Kirim Pesanan ke Kasir</button>
                        <p class="sales-checkout-hint">Stok belum dipotong. Simpan tautan nomor pesanan untuk memantau statusnya.</p>
                    </section>
                </aside>
            </div>
        </form>
    </main>

    <script>
        (function () {
            // Filter kategori + pencarian
            const searchInput = document.getElementById('customer-product-search');
            const emptyNotice = document.getElementById('customer-search-empty');
            const cards = document.querySelectorAll('.customer-order-product');
            const tabs = document.querySelectorAll('[data-category-tab]');
            let activeCategory = '';

            function applyFilter() {
                const keyword = searchInput.value.trim().toLowerCase();
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
            searchInput.addEventListener('input', applyFilter);

            // Tombol + Tambah dan stepper jumlah
            document.querySelectorAll('[data-qty-control]').forEach(function (box) {
                const addBtn = box.querySelector('[data-qty-add]');
                const stepper = box.querySelector('.customer-order-stepper');
                const qtyInput = box.querySelector('input');
                const minus = box.querySelector('[data-qty-minus]');
                const plus = box.querySelector('[data-qty-plus]');
                const max = parseInt(qtyInput.max, 10) || 0;

                function sync() {
                    const qty = parseInt(qtyInput.value, 10) || 0;
                    addBtn.hidden = qty > 0;
                    stepper.hidden = qty === 0;
                    plus.disabled = qty >= max;
                }

                function setQty(value) {
                    qtyInput.value = Math.min(Math.max(value, 0), max);
                    qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
                    qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                addBtn.addEventListener('click', function () { setQty(1); });
                minus.addEventListener('click', function () { setQty((parseInt(qtyInput.value, 10) || 0) - 1); });
                plus.addEventListener('click', function () { setQty((parseInt(qtyInput.value, 10) || 0) + 1); });

                // Saat diketik manual: rapikan angka, tanpa memicu change lagi
                qtyInput.addEventListener('change', function () {
                    const raw = parseInt(qtyInput.value, 10) || 0;
                    const qty = Math.min(Math.max(raw, 0), max);
                    if (String(qty) !== qtyInput.value) {
                        qtyInput.value = qty;
                        qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    sync();
                });
                sync();
            });

            // Keranjang di samping
            const orderForm = document.querySelector('[data-customer-order-form]');
            const cartList = document.querySelector('[data-cart-list]');
            const cartCount = document.querySelector('[data-cart-count]');
            const qtyInputs = Array.from(document.querySelectorAll('[data-customer-product-quantity]'));

            function rupiah(n) { return 'Rp' + n.toLocaleString('id-ID'); }

            function renderCart() {
                cartList.replaceChildren();
                let count = 0;

                qtyInputs.forEach(function (qtyInput) {
                    const qty = parseInt(qtyInput.value, 10) || 0;
                    if (qty <= 0) return;
                    count += qty;
                    const price = parseInt(qtyInput.dataset.productPrice, 10) || 0;

                    const row = document.createElement('div');
                    row.className = 'customer-order-cart-row';

                    const info = document.createElement('div');
                    const name = document.createElement('strong');
                    name.textContent = qtyInput.dataset.productName;
                    const detail = document.createElement('span');
                    detail.textContent = qty + ' × ' + rupiah(price);
                    info.append(name, detail);

                    const subtotal = document.createElement('b');
                    subtotal.textContent = rupiah(qty * price);

                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.textContent = '×';
                    remove.setAttribute('aria-label', 'Hapus ' + qtyInput.dataset.productName);
                    remove.addEventListener('click', function () {
                        qtyInput.value = 0;
                        qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
                        qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
                    });

                    row.append(info, subtotal, remove);
                    cartList.append(row);
                });

                if (count === 0) {
                    const empty = document.createElement('p');
                    empty.className = 'customer-order-cart-empty';
                    empty.textContent = 'Keranjang masih kosong. Tekan + Tambah pada produk.';
                    cartList.append(empty);
                }
                cartCount.textContent = count;
            }

            orderForm.addEventListener('input', renderCart);
            orderForm.addEventListener('change', renderCart);
            renderCart();
        })();
    </script>
</body>
</html>