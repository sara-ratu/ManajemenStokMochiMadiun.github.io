<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Kelola Stok | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page catalog-page stock-page">
    <header class="catalog-topbar">
        <a class="catalog-brand" href="{{ route('dashboard') }}" aria-label="Beranda Mochi Madiun">
            <img class="mochi-logo" src="{{ asset('images/mochi-madiun-logo.png') }}" alt="">
            @php($stockNow = now('Asia/Jakarta'))
            <small><time data-live-clock datetime="{{ $stockNow->toIso8601String() }}">{{ $stockNow->locale('id')->translatedFormat('l, d F Y') }} · {{ $stockNow->format('H.i') }}</time></small>
        </a>
        <form class="catalog-search" method="GET" action="{{ route('stocks.index') }}" role="search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            <input name="q" type="search" value="{{ $search }}" placeholder="Cari produk, kategori, atau petugas..." maxlength="100" aria-label="Cari riwayat stok">
        </form>
    </header>

    <div class="products-app-shell">
        <aside class="app-sidebar">
            <p class="sidebar-section-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <a class="sidebar-nav-link" href="{{ route('dashboard') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z"/></svg>
                    <span>Beranda</span>
                </a>
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('stocks.index') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4z"/><path d="M4 7l2-4h12l2 4M12 11v6m-3-3h6"/></svg>
                    <span>Kelola Stok</span>
                </a>
                <a class="sidebar-nav-link" href="{{ route('customer-orders.cashier.index') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/><path d="m8 19 4 3 4-3"/></svg>
                    <span>Order</span>
                </a>
                <a class="sidebar-nav-link" href="{{ route('sales.index') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h3m2 0h3m-8 4h8"/></svg>
                    <span>Transaksi</span>
                </a>
                <a class="sidebar-nav-link" href="{{ route('profile.show') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <span>Profil</span>
                </a>
            </nav>
            <div class="sidebar-footer">Mochi Madiun <span>Manajemen Toko</span></div>
        </aside>

        <div class="products-workspace">
            <header class="products-topbar">
                <div class="topbar-page-label">Kelola Stok</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ auth()->user()->nama_lengkap ?: 'Pengguna' }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="logout-button" type="submit">Keluar</button>
                    </form>
                </div>
            </header>

            <main class="products-main stock-main">
                @if (session('status'))
                    <div class="action-notice action-notice-success" role="status">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="action-notice action-notice-error" role="alert">{{ session('error') }}</div>
                @endif

                <section class="products-heading stock-heading">
                    <div>
                        <p class="products-eyebrow">PERSEDIAAN TOKO</p>
                        <p class="products-subtitle">Catat stok masuk dan pantau riwayat persediaan produk.</p>
                    </div>
                    @can('manage-stocks')
                        <button class="add-product-button" type="button" data-open-product-dialog="add-stock-dialog">
                            <span aria-hidden="true">+</span>
                            Catat Stok Masuk
                        </button>
                    @endcan
                </section>

                <section class="stock-summary" aria-label="Ringkasan persediaan">
                    <article class="stock-summary-card">
                        <span class="stock-summary-icon stock-summary-icon-pink" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4.5 7.5 7.5 4 7.5-4M12 12v9"/></svg>
                        </span>
                        <div><span>Total Produk</span><strong>{{ number_format($productCount, 0, ',', '.') }}</strong></div>
                    </article>
                    <article class="stock-summary-card">
                        <span class="stock-summary-icon stock-summary-icon-green" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 3v18m-8-8 8 8 8-8"/></svg>
                        </span>
                        <div><span>Total Stok Saat Ini</span><strong>{{ number_format($totalStock, 0, ',', '.') }} <small>pcs</small></strong></div>
                    </article>
                    <article class="stock-summary-card">
                        <span class="stock-summary-icon stock-summary-icon-purple" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M5 4h14v17H5zM8 8h8M8 12h8M8 16h5"/></svg>
                        </span>
                        <div><span>Catatan Ditampilkan</span><strong>{{ number_format($stocks->total(), 0, ',', '.') }}</strong></div>
                    </article>
                </section>

                <section class="stock-history-card" aria-label="Riwayat stok masuk">
                    <div class="stock-history-heading">
                        <div>
                            <h2>Riwayat Stok</h2>
                            <p>Catatan stok masuk dan keluar tersimpan sebagai riwayat.</p>
                        </div>
                        @if ($search !== '')
                            <a class="stock-clear-filter" href="{{ route('stocks.index') }}">Hapus pencarian</a>
                        @endif
                    </div>
                    <div class="catalog-table-scroll">
                        <table class="stock-table">
                            <thead>
                                <tr>
                                    <th scope="col">ID Stok</th>
                                    <th scope="col">Tanggal</th>
                                    <th scope="col">ID Produk</th>
                                    <th scope="col">Nama Produk</th>
                                    <th scope="col">Kategori</th>
                                    <th scope="col">Pergerakan</th>
                                    <th scope="col">Keterangan</th>
                                    <th scope="col">Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($stocks as $stock)
                                    <tr>
                                        <td class="stock-id-cell">#{{ $stock->id_stok }}</td>
                                        <td>{{ \Illuminate\Support\Carbon::parse($stock->tanggal)->locale('id')->translatedFormat('d M Y') }}</td>
                                        <td>{{ $stock->ms_produk_id_produk }}</td>
                                        <td class="stock-product-cell">{{ $stock->nama_produk ?: 'Produk tidak ditemukan' }}</td>
                                        <td>{{ $stock->nama_kategori ?: 'Tanpa kategori' }}</td>
                                        <td>
                                            @if ((int) $stock->jumlah < 0)
                                                <span class="stock-incoming-badge stock-outgoing-badge">{{ number_format((int) $stock->jumlah, 0, ',', '.') }} pcs</span>
                                            @else
                                                <span class="stock-incoming-badge">+{{ number_format((int) $stock->jumlah, 0, ',', '.') }} pcs</span>
                                            @endif
                                        </td>
                                        <td>{{ $stock->keterangan ?: '—' }}</td>
                                        <td>{{ $stock->nama_petugas ?: 'Pengguna #'.$stock->ms_user_id_user }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="stock-empty-cell" colspan="8">
                                            <span aria-hidden="true">📦</span>
                                            <strong>{{ $search !== '' ? 'Catatan stok tidak ditemukan' : 'Belum ada catatan stok' }}</strong>
                                            <span>{{ $search !== '' ? 'Coba kata pencarian lain.' : 'Catat stok masuk pertama untuk mulai mengelola persediaan.' }}</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($stocks->hasPages())
                        <div class="catalog-pagination">{{ $stocks->links() }}</div>
                    @endif
                </section>
            </main>
        </div>
    </div>

    @can('manage-stocks')
        <dialog
            class="product-dialog product-dialog-wide"
            id="add-stock-dialog"
            aria-labelledby="add-stock-title"
            data-product-dialog
            @if ($errors->any()) data-open-on-load @endif
        >
            <form class="product-dialog-form" action="{{ route('stocks.store') }}" method="POST">
                @csrf
                <h2 id="add-stock-title">Catat Stok Masuk</h2>
                <p class="stock-dialog-hint">Jumlah yang dicatat akan ditambahkan ke stok produk saat ini.</p>

                @if ($errors->any())
                    <div class="dialog-validation-summary" role="alert">Periksa kembali data stok yang Anda masukkan.</div>
                @endif

                <div class="dialog-form-grid">
                    <div class="dialog-form-field">
                        <label for="stock-product-search">Cari Produk</label>
                        <input id="stock-product-search" type="search" placeholder="Ketik nama produk..." autocomplete="off" data-stock-product-search>
                        <label for="stock-product-id">Produk</label>
                        <select id="stock-product-id" name="id_produk" required data-stock-product-select @if ($errors->has('id_produk')) aria-describedby="stock-product-error" @endif>
                            <option value="">Pilih produk</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id_produk }}" @selected((string) old('id_produk') === (string) $product->id_produk)>
                                    #{{ $product->id_produk }} · {{ $product->nama_produk ?: 'Produk tanpa nama' }} · {{ $product->category?->nama_kategori ?: 'Tanpa kategori' }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_produk')<span class="form-field-error" id="stock-product-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="dialog-form-field">
                        <label for="stock-tanggal">Tanggal</label>
                        <input id="stock-tanggal" name="tanggal" type="date" value="{{ old('tanggal', now('Asia/Jakarta')->toDateString()) }}" required @if ($errors->has('tanggal')) aria-describedby="stock-date-error" @endif>
                        @error('tanggal')<span class="form-field-error" id="stock-date-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="dialog-form-field">
                        <label for="stock-jumlah">Jumlah Stok Masuk <span>(pcs)</span></label>
                        <input id="stock-jumlah" name="jumlah" type="number" min="1" max="2147483647" step="1" value="{{ old('jumlah') }}" placeholder="Contoh: 20" required @if ($errors->has('jumlah')) aria-describedby="stock-quantity-error" @endif>
                        @error('jumlah')<span class="form-field-error" id="stock-quantity-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="dialog-form-field">
                        <label for="stock-keterangan">Keterangan <span>(opsional, maks. 45 karakter)</span></label>
                        <input id="stock-keterangan" name="keterangan" type="text" maxlength="45" value="{{ old('keterangan') }}" placeholder="Contoh: Produksi pagi" @if ($errors->has('keterangan')) aria-describedby="stock-note-error" @endif>
                        @error('keterangan')<span class="form-field-error" id="stock-note-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="product-dialog-actions">
                    <button class="dialog-cancel-button" type="button" data-close-product-dialog>Batal</button>
                    <button class="dialog-submit-button" type="submit">Simpan Stok</button>
                </div>
            </form>
        </dialog>
    @endcan
</body>
</html>
