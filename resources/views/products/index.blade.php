<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Produk | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page catalog-page">
    <header class="catalog-topbar">
        <a class="catalog-brand" href="{{ route('products.index') }}" aria-label="Mochi Madiun">
            <span>MOCHI MADIUN</span>
            @php($catalogNow = now('Asia/Jakarta'))
            <small><time data-live-clock datetime="{{ $catalogNow->toIso8601String() }}">{{ $catalogNow->locale('id')->translatedFormat('l, d F Y') }} · {{ $catalogNow->format('H.i') }}</time></small>
        </a>
        <form class="catalog-search" method="GET" action="{{ route('products.index') }}" role="search">
            @if ($selectedCategory !== '')
                <input type="hidden" name="kategori" value="{{ $selectedCategory }}">
            @endif
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            <input name="q" type="search" value="{{ $search }}" placeholder="Cari kategori atau produk..." maxlength="100" aria-label="Cari produk">
        </form>
    </header>

    <div class="products-app-shell">
        <aside class="app-sidebar">
            <p class="sidebar-section-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('products.index') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4.5 7.5 7.5 4 7.5-4M12 12v9"/></svg>
                    <span>Produk</span>
                </a>
                <a class="sidebar-nav-link" href="{{ route('stocks.index') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4z"/><path d="M4 7l2-4h12l2 4M12 11v6m-3-3h6"/></svg>
                    <span>Kelola Stok</span>
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
                <div class="topbar-page-label">Produk</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ auth()->user()->nama_lengkap ?: 'Pengguna' }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="logout-button" type="submit">Keluar</button>
                    </form>
                </div>
            </header>

            <main class="products-main products-main-catalog stock-main">
                @if (session('status'))
                    <div class="action-notice action-notice-success" role="status">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="action-notice action-notice-error" role="alert">{{ session('error') }}</div>
                @endif

                <section class="products-heading stock-heading">
                    <div>
                        <p class="products-eyebrow">KATALOG TOKO</p>
                        <p class="products-subtitle">Kelola produk, kategori, harga, dan informasi stok.</p>
                    </div>
                    @can('manage-products')
                        <button class="add-product-button" type="button" data-open-product-dialog="add-product-dialog">
                            <span aria-hidden="true">+</span>
                            Tambah Produk
                        </button>
                    @endcan
                </section>

                <section class="stock-summary" aria-label="Ringkasan katalog produk">
                    <article class="stock-summary-card">
                        <span class="stock-summary-icon stock-summary-icon-pink" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4.5 7.5 7.5 4 7.5-4M12 12v9"/></svg>
                        </span>
                        <div><span>Total Produk</span><strong>{{ number_format($productCount, 0, ',', '.') }}</strong></div>
                    </article>
                    <article class="stock-summary-card">
                        <span class="stock-summary-icon stock-summary-icon-purple" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 5h16M4 12h16M4 19h16"/><circle cx="8" cy="5" r="1"/><circle cx="16" cy="12" r="1"/><circle cx="10" cy="19" r="1"/></svg>
                        </span>
                        <div><span>Total Kategori</span><strong>{{ number_format($categoryCount, 0, ',', '.') }}</strong></div>
                    </article>
                    <article class="stock-summary-card">
                        <span class="stock-summary-icon stock-summary-icon-green" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 3v18m-8-8 8 8 8-8"/></svg>
                        </span>
                        <div><span>Total Stok Saat Ini</span><strong>{{ number_format($totalStock, 0, ',', '.') }} <small>pcs</small></strong></div>
                    </article>
                </section>

                <section class="catalog-toolbar" aria-label="Filter kategori produk">
                    <nav class="category-pills" aria-label="Kategori produk">
                        <a
                            class="category-pill {{ $selectedCategory === '' ? 'category-pill-active' : '' }}"
                            href="{{ route('products.index', array_filter(['q' => $search], fn ($value) => $value !== null && $value !== '')) }}"
                            @if ($selectedCategory === '') aria-current="page" @endif
                        >
                            <span class="category-pill-dot" aria-hidden="true"></span>
                            Semua
                        </a>
                        @foreach ($categories as $category)
                            <a
                                class="category-pill category-pill-tone-{{ $loop->iteration % 5 }} {{ (string) $selectedCategory === (string) $category->id_kategori ? 'category-pill-active' : '' }}"
                                href="{{ route('products.index', array_filter(['q' => $search, 'kategori' => $category->id_kategori], fn ($value) => $value !== null && $value !== '')) }}"
                                @if ((string) $selectedCategory === (string) $category->id_kategori) aria-current="page" @endif
                            >
                                <span class="category-pill-dot" aria-hidden="true"></span>
                                {{ $category->nama_kategori ?: 'Tanpa nama' }}
                            </a>
                        @endforeach
                    </nav>
                </section>

                <section class="stock-history-card catalog-table-card" aria-label="Daftar produk">
                    <div class="stock-history-heading">
                        <div>
                            <h2>Daftar Produk</h2>
                            <p>Produk dengan informasi kategori, harga, dan stok saat ini.</p>
                        </div>
                        @if ($search !== '' || $selectedCategory !== '')
                            <a class="stock-clear-filter" href="{{ route('products.index') }}">Hapus filter</a>
                        @endif
                    </div>
                    <div class="catalog-table-scroll">
                        <table class="product-table {{ auth()->user()->can('manage-products') ? 'product-table-manage' : '' }}">
                            <thead>
                                <tr>
                                    <th class="product-col-id" scope="col">ID Produk</th>
                                    <th class="product-col-name" scope="col">Nama Produk</th>
                                    <th class="product-col-category-id" scope="col">ID Kategori</th>
                                    <th class="product-col-category" scope="col">Nama Kategori</th>
                                    <th class="product-col-price" scope="col">Harga</th>
                                    <th class="product-col-stock" scope="col">Stok</th>
                                    <th class="product-col-image" scope="col">Gambar</th>
                                    @can('manage-products')
                                        <th class="product-col-actions" scope="col">Aksi</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    <tr>
                                        <td class="product-col-id">{{ $product->id_produk }}</td>
                                        <td class="product-col-name catalog-product-name">{{ $product->nama_produk ?: 'Produk tanpa nama' }}</td>
                                        <td class="product-col-category-id">{{ $product->id_kategori ?? '—' }}</td>
                                        <td class="product-col-category">{{ $product->category?->nama_kategori ?: 'Lainnya' }}</td>
                                        <td class="product-col-price catalog-product-price">
                                            @if ($product->harga !== null)
                                                Rp{{ number_format((int) $product->harga, 0, ',', '.') }}
                                            @else
                                                <span class="product-unavailable">Belum tersedia</span>
                                            @endif
                                        </td>
                                        <td class="product-col-stock">
                                            @if ((int) $product->stok > 0)
                                                <span class="catalog-stock-badge catalog-stock-available">{{ number_format((int) $product->stok, 0, ',', '.') }} pcs</span>
                                            @else
                                                <span class="catalog-stock-badge catalog-stock-empty">Habis</span>
                                            @endif
                                        </td>
                                        <td class="product-col-image">
                                            <div class="catalog-product-image">
                                                @if ($product->gambar)
                                                    <img
                                                        src="{{ \Illuminate\Support\Str::startsWith($product->gambar, ['http://', 'https://']) ? $product->gambar : \Illuminate\Support\Facades\Storage::disk('public')->url($product->gambar) }}"
                                                        alt="{{ $product->nama_produk ?: 'Produk mochi' }}"
                                                        loading="lazy"
                                                    >
                                                @else
                                                    <span aria-label="Foto belum tersedia" role="img">🍡</span>
                                                @endif
                                            </div>
                                        </td>
                                        @can('manage-products')
                                            <td class="product-col-actions">
                                                <div class="catalog-row-actions">
                                                    <button class="catalog-icon-action catalog-edit-action" type="button" data-open-product-dialog="edit-product-{{ $product->id_produk }}" aria-label="Ubah {{ $product->nama_produk }}" title="Ubah produk">
                                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                                    </button>
                                                    <form action="{{ route('products.destroy', $product) }}" method="POST" data-confirm-delete>
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="catalog-icon-action catalog-delete-action" type="submit" aria-label="Hapus {{ $product->nama_produk }}" title="Hapus produk">
                                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"/></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="catalog-empty-cell" colspan="{{ auth()->user()->can('manage-products') ? 8 : 7 }}">
                                            <span aria-hidden="true">🍡</span>
                                            <strong>Produk belum ditemukan</strong>
                                            <span>Coba kata pencarian atau kategori lain.</span>
                                            @if ($search !== '' || $selectedCategory !== '')
                                                <a href="{{ route('products.index') }}">Hapus filter</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($products->hasPages())
                        <div class="catalog-pagination">{{ $products->links() }}</div>
                    @endif
                </section>
            </main>
        </div>
    </div>

    @can('manage-products')
        <dialog
            class="product-dialog product-dialog-wide"
            id="add-product-dialog"
            aria-labelledby="add-product-dialog-title"
            data-product-dialog
            @if ($errors->any() && ! old('edit_product_id')) data-open-on-load @endif
        >
            <form class="product-dialog-form" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" data-category-form>
                @csrf
                <h2 id="add-product-dialog-title">Tambah Produk</h2>

                @if ($errors->any() && ! old('edit_product_id'))
                    <div class="dialog-validation-summary" role="alert">Periksa kembali data produk yang Anda masukkan.</div>
                @endif

                <div class="dialog-form-grid">
                    <div class="dialog-form-field">
                        <label for="dialog-nama-produk">Nama Produk</label>
                        <input
                            id="dialog-nama-produk"
                            name="nama_produk"
                            type="text"
                            value="{{ old('nama_produk') }}"
                            maxlength="30"
                            placeholder="Mis. Mochi original"
                            required
                            @if ($errors->has('nama_produk')) aria-describedby="dialog-nama-produk-error" @endif
                        >
                        @error('nama_produk')<span class="form-field-error" id="dialog-nama-produk-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="dialog-form-field">
                        <label for="dialog-id-kategori">ID Kategori <span>(pilih langsung atau cari nama kategori)</span></label>
                        <input
                            id="dialog-id-kategori"
                            name="id_kategori"
                            type="number"
                            value="{{ old('id_kategori') }}"
                            min="1"
                            step="1"
                            placeholder="Masukkan ID kategori"
                            data-category-id-input
                            @if ($errors->has('id_kategori')) aria-describedby="dialog-kategori-error" @endif
                        >
                        <label for="dialog-kategori-cari">Nama Kategori <span>(ketik nama, lalu pilih)</span></label>
                        <input
                            id="dialog-kategori-cari"
                            name="kategori_cari"
                            type="search"
                            value="{{ old('kategori_cari') }}"
                            placeholder="Ketik nama kategori..."
                            autocomplete="off"
                            data-category-search
                        >
                        <select id="dialog-kategori" data-category-select>
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id_kategori }}" @selected((string) old('id_kategori') === (string) $category->id_kategori)>
                                    {{ $category->nama_kategori ?: 'Tanpa nama' }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_kategori')<span class="form-field-error" id="dialog-kategori-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="dialog-form-field">
                        <label for="dialog-harga">Harga (Rp)</label>
                        <input
                            id="dialog-harga"
                            name="harga"
                            type="number"
                            value="{{ old('harga') }}"
                            min="0"
                            max="2147483647"
                            step="1"
                            placeholder="400"
                            required
                            @if ($errors->has('harga')) aria-describedby="dialog-harga-error" @endif
                        >
                        @error('harga')<span class="form-field-error" id="dialog-harga-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="dialog-form-field product-dialog-image-field">
                        <label for="dialog-gambar-url">Gambar Produk <span>(URL atau unggah foto)</span></label>
                        <input
                            id="dialog-gambar-url"
                            name="gambar_url"
                            type="url"
                            value="{{ old('gambar_url') }}"
                            maxlength="100"
                            placeholder="https://contoh.com/gambar.jpg"
                            autocomplete="url"
                            @if ($errors->has('gambar_url')) aria-describedby="dialog-gambar-url-error" @endif
                        >
                        @error('gambar_url')<span class="form-field-error" id="dialog-gambar-url-error">{{ $message }}</span>@enderror
                        <input id="dialog-gambar" name="gambar" type="file" accept="image/*">
                        @error('gambar')<span class="form-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="product-dialog-actions">
                    <button class="dialog-cancel-button" type="button" data-close-product-dialog>Batal</button>
                    <button class="dialog-submit-button" type="submit">Simpan Produk</button>
                </div>
            </form>
        </dialog>

        @foreach ($products as $product)
            <dialog
                class="product-dialog product-dialog-wide"
                id="edit-product-{{ $product->id_produk }}"
                aria-labelledby="edit-product-title-{{ $product->id_produk }}"
                data-product-dialog
                @if ($errors->any() && (string) old('edit_product_id') === (string) $product->id_produk) data-open-on-load @endif
            >
                <form class="product-dialog-form" action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" data-category-form>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="edit_product_id" value="{{ $product->id_produk }}">
                    <h2 id="edit-product-title-{{ $product->id_produk }}">Ubah Produk</h2>

                    @if ($errors->any() && (string) old('edit_product_id') === (string) $product->id_produk)
                        <div class="dialog-validation-summary" role="alert">Periksa kembali data produk yang Anda masukkan.</div>
                    @endif

                    <div class="dialog-form-grid">
                        <div class="dialog-form-field">
                            <label for="edit-id-produk-{{ $product->id_produk }}">ID Produk</label>
                            <input id="edit-id-produk-{{ $product->id_produk }}" type="text" value="{{ $product->id_produk }}" readonly>
                        </div>
                        <div class="dialog-form-field">
                            <label for="edit-nama-produk-{{ $product->id_produk }}">Nama Produk</label>
                            <input id="edit-nama-produk-{{ $product->id_produk }}" name="nama_produk" type="text" value="{{ old('edit_product_id') == $product->id_produk ? old('nama_produk', $product->nama_produk) : $product->nama_produk }}" maxlength="30" required>
                            @if ((string) old('edit_product_id') === (string) $product->id_produk)
                                @error('nama_produk')<span class="form-field-error">{{ $message }}</span>@enderror
                            @endif
                        </div>
                        <div class="dialog-form-field">
                            <label for="edit-id-kategori-{{ $product->id_produk }}">ID Kategori</label>
                            <input id="edit-id-kategori-{{ $product->id_produk }}" name="id_kategori" type="number" value="{{ (string) old('edit_product_id') === (string) $product->id_produk ? old('id_kategori', $product->id_kategori) : $product->id_kategori }}" min="1" step="1" placeholder="Masukkan ID kategori" data-category-id-input>
                        </div>
                        <div class="dialog-form-field">
                            <label for="edit-kategori-cari-{{ $product->id_produk }}">Nama Kategori</label>
                            <input id="edit-kategori-cari-{{ $product->id_produk }}" name="kategori_cari" type="search" value="{{ (string) old('edit_product_id') === (string) $product->id_produk ? old('kategori_cari', $product->category?->nama_kategori) : $product->category?->nama_kategori }}" placeholder="Ketik nama kategori..." autocomplete="off" data-category-search>
                            <select id="edit-kategori-{{ $product->id_produk }}" data-category-select>
                                <option value="">Pilih kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id_kategori }}" @selected((string) ((string) old('edit_product_id') === (string) $product->id_produk ? old('id_kategori', $product->id_kategori) : $product->id_kategori) === (string) $category->id_kategori)>{{ $category->nama_kategori ?: 'Tanpa nama' }}</option>
                                @endforeach
                            </select>
                            @if ((string) old('edit_product_id') === (string) $product->id_produk)
                                @error('id_kategori')<span class="form-field-error">{{ $message }}</span>@enderror
                            @endif
                        </div>
                        <div class="dialog-form-field">
                            <label for="edit-harga-{{ $product->id_produk }}">Harga (Rp)</label>
                            <input id="edit-harga-{{ $product->id_produk }}" name="harga" type="number" value="{{ old('edit_product_id') == $product->id_produk ? old('harga', $product->harga) : $product->harga }}" min="0" max="2147483647" step="1" required>
                            @if ((string) old('edit_product_id') === (string) $product->id_produk)
                                @error('harga')<span class="form-field-error">{{ $message }}</span>@enderror
                            @endif
                        </div>
                        <div class="dialog-form-field">
                            <label for="edit-stok-{{ $product->id_produk }}">Stok <span>(dikelola di menu stok)</span></label>
                            <input id="edit-stok-{{ $product->id_produk }}" type="text" value="{{ $product->stok === null ? 'Belum diatur' : number_format((int) $product->stok, 0, ',', '.').' pcs' }}" readonly>
                        </div>
                        <div class="dialog-form-field product-dialog-image-field">
                            <label for="edit-gambar-url-{{ $product->id_produk }}">Gambar Produk <span>(URL atau unggah foto)</span></label>
                            <input id="edit-gambar-url-{{ $product->id_produk }}" name="gambar_url" type="url" value="{{ (string) old('edit_product_id') === (string) $product->id_produk ? old('gambar_url', '') : '' }}" maxlength="100" placeholder="https://contoh.com/gambar.jpg">
                            <label for="edit-gambar-{{ $product->id_produk }}">atau unggah foto <span>(maks. 2 MB)</span></label>
                            <input id="edit-gambar-{{ $product->id_produk }}" name="gambar" type="file" accept="image/*">
                            @if ($product->gambar)
                                <div class="dialog-current-product-image">
                                    <img src="{{ \Illuminate\Support\Str::startsWith($product->gambar, ['http://', 'https://']) ? $product->gambar : \Illuminate\Support\Facades\Storage::disk('public')->url($product->gambar) }}" alt="Foto {{ $product->nama_produk }}">
                                    <label><input type="checkbox" name="hapus_gambar" value="1" @checked((string) old('edit_product_id') === (string) $product->id_produk && old('hapus_gambar'))> Hapus foto saat disimpan</label>
                                </div>
                            @endif
                            @if ((string) old('edit_product_id') === (string) $product->id_produk)
                                @error('gambar_url')<span class="form-field-error">{{ $message }}</span>@enderror
                                @error('gambar')<span class="form-field-error">{{ $message }}</span>@enderror
                            @endif
                        </div>
                    </div>

                    <div class="product-dialog-actions">
                        <button class="dialog-cancel-button" type="button" data-close-product-dialog>Batal</button>
                        <button class="dialog-submit-button" type="submit">Simpan Perubahan</button>
                    </div>
                </form>
            </dialog>
        @endforeach

        <dialog class="delete-confirm-dialog" aria-labelledby="delete-confirm-title" aria-describedby="delete-confirm-message" data-delete-dialog>
            <div class="delete-confirm-heading">
                <h2 id="delete-confirm-title">Hapus Produk?</h2>
                <button type="button" class="delete-confirm-close" aria-label="Tutup" data-close-delete-dialog>&times;</button>
            </div>
            <div class="delete-confirm-body">
                <p id="delete-confirm-message">Yakin ingin menghapus produk ini?</p>
                <div class="delete-confirm-actions">
                    <button type="button" class="delete-confirm-accept" data-confirm-delete-action>Ya, hapus</button>
                    <button type="button" class="delete-confirm-cancel" data-close-delete-dialog>Batal</button>
                </div>
            </div>
        </dialog>
    @endcan
</body>
</html>
