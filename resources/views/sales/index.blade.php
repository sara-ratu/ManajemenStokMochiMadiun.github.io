<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Transaksi Penjualan | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page catalog-page">
    <header class="catalog-topbar">
        <a class="catalog-brand" href="{{ route('dashboard') }}" aria-label="Beranda Mochi Madiun">
            <img class="mochi-logo" src="{{ asset('images/mochi-madiun-logo.png') }}" alt="">
            @php($salesNow = now('Asia/Jakarta'))
            <small><time data-live-clock datetime="{{ $salesNow->toIso8601String() }}">{{ $salesNow->locale('id')->translatedFormat('l, d F Y') }} · {{ $salesNow->format('H.i') }}</time></small>
        </a>
        @can('cashier')
            <form class="catalog-search" method="GET" action="{{ route('sales.index') }}" role="search">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
                <input name="q" type="search" value="{{ $search }}" placeholder="Cari produk atau kategori..." maxlength="100" aria-label="Cari produk untuk transaksi">
            </form>
        @endcan
    </header>

    <div class="products-app-shell">
        <aside class="app-sidebar">
            <p class="sidebar-section-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <a class="sidebar-nav-link" href="{{ route('dashboard') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z"/></svg>
                    <span>Beranda</span>
                </a>
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('sales.index') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h3m2 0h3m-8 4h8"/></svg>
                    <span>Transaksi</span>
                </a>
                @can('manage-employees')
                    <a class="sidebar-nav-link" href="{{ route('employees.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 4a4 4 0 0 1 0 8m2 2a7 7 0 0 1 4 7"/></svg>
                        <span>Data Karyawan</span>
                    </a>
                @endcan
                @can('manage-products')
                    <a class="sidebar-nav-link" href="{{ route('products.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4.5 7.5 7.5 4 7.5-4M12 12v9"/></svg>
                        <span>Produk</span>
                    </a>
                @endcan
                @can('manage-stocks')
                    <a class="sidebar-nav-link" href="{{ route('stocks.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4z"/><path d="M4 7l2-4h12l2 4M12 11v6m-3-3h6"/></svg>
                        <span>Kelola Stok</span>
                    </a>
                @endcan
                @can('cashier')
                    <a class="sidebar-nav-link" href="{{ route('customer-orders.cashier.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/><path d="m8 19 4 3 4-3"/></svg>
                        <span>Order</span>
                    </a>
                @endcan
                <a class="sidebar-nav-link" href="{{ route('profile.show') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <span>Profil</span>
                </a>
            </nav>
            <div class="sidebar-footer">Mochi Madiun <span>Manajemen Toko</span></div>
        </aside>

        <div class="products-workspace">
            <header class="products-topbar">
                <div class="topbar-page-label">{{ auth()->user()->can('cashier') ? 'Transaksi Penjualan' : 'Laporan Penjualan' }}</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ auth()->user()->nama_lengkap ?: 'Pengguna' }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="logout-button" type="submit">Keluar</button>
                    </form>
                </div>
            </header>

            <main class="products-main sales-main">
                @if (session('status'))
                    <div class="action-notice action-notice-success" role="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="action-notice action-notice-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <section class="products-heading stock-heading">
                    <div>
                        <p class="products-eyebrow">{{ auth()->user()->can('cashier') ? 'KASIR' : 'LAPORAN PENJUALAN' }}</p>
                        <p class="products-subtitle">{{ auth()->user()->can('cashier') ? 'Pilih produk untuk dimasukkan ke keranjang, lalu proses pembayaran.' : 'Pantau ringkasan dan riwayat transaksi penjualan.' }}</p>
                    </div>
                </section>

                <section class="stock-summary sales-summary" aria-label="Ringkasan transaksi">
                    <article class="stock-summary-card">
                        <span class="stock-summary-icon stock-summary-icon-pink" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4.5 7.5 7.5 4 7.5-4M12 12v9"/></svg>
                        </span>
                        <div><span>Produk Tersedia</span><strong>{{ number_format($productCount, 0, ',', '.') }}</strong></div>
                    </article>
                    <article class="stock-summary-card">
                        <span class="stock-summary-icon stock-summary-icon-green" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M3 12h18M12 3v18"/><circle cx="12" cy="12" r="9"/></svg>
                        </span>
                        <div><span>Transaksi Selesai</span><strong>{{ number_format($salesCount, 0, ',', '.') }}</strong></div>
                    </article>
                    <article class="stock-summary-card">
                        <span class="stock-summary-icon stock-summary-icon-purple" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/></svg>
                        </span>
                        <div><span>Total Penjualan Lunas</span><strong>Rp{{ number_format($salesTotal, 0, ',', '.') }}</strong></div>
                    </article>
                </section>

                @can('cashier')
                    <section class="sales-workspace" aria-label="Kasir dan keranjang">
                    <div class="sales-products-panel">
                        <div class="stock-history-heading sales-panel-heading">
                            <div>
                                <h2>Pilih Produk</h2>
                                <p>{{ $products->count() }} produk{{ $search !== '' ? ' cocok dengan pencarian' : ' tersedia' }}</p>
                            </div>
                            @if ($search !== '')
                                <a class="stock-clear-filter" href="{{ route('sales.index') }}">Hapus pencarian</a>
                            @endif
                        </div>
                        <div class="sales-product-grid">
                            @forelse ($products as $product)
                                <article class="sales-product-card">
                                    <div class="sales-product-image">
                                        @if ($product->gambar)
                                            <img
                                                src="{{ $product->gambar_url }}"
                                                alt="{{ $product->nama_produk ?: 'Produk mochi' }}"
                                                loading="lazy"
                                            >
                                        @else
                                            <span aria-hidden="true">🍡</span>
                                        @endif
                                    </div>
                                    <div class="sales-product-info">
                                        <span class="sales-product-category">{{ $product->category?->nama_kategori ?: 'Tanpa kategori' }}</span>
                                        <h3>{{ $product->nama_produk ?: 'Produk tanpa nama' }}</h3>
                                        <strong>Rp{{ number_format((int) $product->harga, 0, ',', '.') }}</strong>
                                        <span class="sales-product-stock">{{ number_format(max((int) $product->stok, 0), 0, ',', '.') }} pcs tersedia</span>
                                    </div>
                                    <button
                                        class="sales-add-button"
                                        type="button"
                                        data-add-sale-product
                                        data-product-id="{{ $product->id_produk }}"
                                        data-product-name="{{ $product->nama_produk ?: 'Produk tanpa nama' }}"
                                        data-product-price="{{ (int) $product->harga }}"
                                        data-product-stock="{{ max((int) $product->stok, 0) }}"
                                        @disabled((int) $product->stok <= 0)
                                    >
                                        <span aria-hidden="true">+</span> Tambah
                                    </button>
                                </article>
                            @empty
                                <div class="sales-products-empty">
                                    <span aria-hidden="true">🍡</span>
                                    <strong>{{ $search !== '' ? 'Produk tidak ditemukan' : 'Belum ada produk' }}</strong>
                                    <span>{{ $search !== '' ? 'Coba kata pencarian lain.' : 'Tambahkan produk terlebih dahulu sebelum bertransaksi.' }}</span>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <aside class="sales-cart-panel">
                        <div class="sales-cart-heading">
                            <div>
                                <h2>Keranjang</h2>
                                <span><span data-cart-count>0</span> item</span>
                            </div>
                            <button type="button" class="sales-clear-cart" data-clear-sale-cart disabled>Kosongkan</button>
                        </div>
                        <form class="sales-checkout-form" action="{{ route('sales.store') }}" method="POST" data-sales-checkout>
                            @csrf
                            <div class="sales-cart-items" data-sale-cart data-initial-items="{{ json_encode(old('items', [])) }}">
                                <div class="sales-cart-empty" data-cart-empty>
                                    <span aria-hidden="true">🛒</span>
                                <strong>Keranjang masih kosong</strong>
                                <span>Pilih produk untuk memulai transaksi.</span>
                            </div>
                        </div>
                        <div class="sales-cart-total">
                            <span>Total Belanja</span>
                            <strong data-cart-total>Rp0</strong>
                        </div>
                        <div class="sales-payment-fields">
                            <label for="sales-payment-method">Metode Pembayaran</label>
                            <select id="sales-payment-method" name="metode" required>
                                <option value="Tunai" @selected(old('metode', 'Tunai') === 'Tunai')>Tunai</option>
                                <option value="Transfer" @selected(old('metode') === 'Transfer')>Transfer</option>
                                <option value="QRIS" @selected(old('metode') === 'QRIS')>QRIS</option>
                            </select>
                            <label for="sales-amount-paid">Jumlah Dibayar (Rp)</label>
                            <input id="sales-amount-paid" name="jumlah_bayar" type="number" min="0" max="2147483647" step="1" value="{{ old('jumlah_bayar', 0) }}" required data-amount-paid>
                            <div class="sales-change-row"><span>Kembalian</span><strong data-change-amount>Rp0</strong></div>
                        </div>
                        <button class="sales-checkout-button" type="submit" disabled data-submit-sale>Proses Pembayaran</button>
                        <p class="sales-checkout-hint">Stok akan diperbarui setelah pembayaran berhasil.</p>
                    </form>
                    </aside>
                    </section>
                @endcan

                <section class="stock-history-card sales-history-card" aria-label="Riwayat transaksi penjualan">
                    <div class="stock-history-heading">
                        <div>
                            <h2>Riwayat Transaksi</h2>
                            <p>Daftar transaksi penjualan sesuai rentang tanggal.</p>
                        </div>
                        <span class="sales-history-count">{{ number_format($sales->total(), 0, ',', '.') }} transaksi</span>
                    </div>
                    <form class="sales-history-filter" method="GET" action="{{ route('sales.index') }}">
                        @if ($search !== '')
                            <input type="hidden" name="q" value="{{ $search }}">
                        @endif
                        <div class="sales-history-filter-field">
                            <label for="sales-start-date">Dari tanggal</label>
                            <input id="sales-start-date" type="date" name="start_date" value="{{ $startDate }}">
                        </div>
                        <div class="sales-history-filter-field">
                            <label for="sales-end-date">Sampai tanggal</label>
                            <input id="sales-end-date" type="date" name="end_date" value="{{ $endDate }}">
                        </div>
                        <div class="sales-history-filter-actions">
                            <button class="sales-history-filter-submit" type="submit">Terapkan</button>
                            @if ($startDate || $endDate)
                                <a class="stock-clear-filter" href="{{ route('sales.index', $search !== '' ? ['q' => $search] : []) }}">Reset</a>
                            @endif
                        </div>
                    </form>
                    <div class="catalog-table-scroll">
                        <table class="stock-table sales-history-table">
                            <thead>
                                <tr>
                                    <th scope="col">ID Pesanan</th>
                                    <th scope="col">Tanggal</th>
                                    <th scope="col">Kasir</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sales as $sale)
                                    <tr>
                                        <td class="sales-history-id"><span>#{{ $sale->id_pesanan }}</span></td>
                                        <td class="sales-history-date">
                                            <strong>{{ \Illuminate\Support\Carbon::parse($sale->tanggal)->locale('id')->translatedFormat('d M Y') }}</strong>
                                            <span>{{ \Illuminate\Support\Carbon::parse($sale->tanggal)->format('H.i') }} WIB</span>
                                        </td>
                                        <td class="sales-history-cashier">{{ $sale->nama_kasir ?: 'Pengguna #'.$sale->ms_user_id_user }}</td>
                                        <td class="sales-history-total">Rp{{ number_format((int) $sale->total, 0, ',', '.') }}</td>
                                        <td><span class="sale-status-badge">{{ $sale->status }}</span></td>
                                        <td><a class="sales-detail-link sales-history-action" href="{{ route('sales.show', $sale->id_pesanan) }}">Lihat struk <span aria-hidden="true">→</span></a></td>
                                    </tr>
                                @empty
                                    <tr><td class="stock-empty-cell sales-history-empty" colspan="6"><span aria-hidden="true">🧾</span><strong>Belum ada transaksi</strong><span>Transaksi yang berhasil diproses akan tampil di sini.</span></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($sales->hasPages())
                        <div class="catalog-pagination">{{ $sales->links() }}</div>
                    @endif
                </section>
            </main>
        </div>
    </div>
</body>
</html>
