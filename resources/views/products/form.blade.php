<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $editing ? 'Ubah Produk' : 'Tambah Produk' }} | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page">
    <div class="products-app-shell">
        <aside class="app-sidebar">
            <a class="sidebar-brand" href="{{ route('products.index') }}" aria-label="Mochi Madiun">
                <span class="products-brand-mark" aria-hidden="true">M</span>
                <span class="sidebar-brand-name">MOCHI <strong>MADIUN</strong></span>
            </a>
            <p class="sidebar-section-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('products.index') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4.5 7.5 7.5 4 7.5-4M12 12v9"/></svg>
                    <span>Produk</span>
                </a>
                <a class="sidebar-nav-link" href="{{ route('sales.index') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h3m2 0h3m-8 4h8"/></svg>
                    <span>Laporan Penjualan</span>
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
                <div class="topbar-page-label">{{ $editing ? 'Ubah Produk' : 'Tambah Produk' }}</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ auth()->user()->nama_lengkap }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="logout-button" type="submit">Keluar</button>
                    </form>
                </div>
            </header>

    <main class="products-main product-form-main">
        <a class="profile-back-link" href="{{ route('products.index') }}">‹ Kembali ke produk</a>
        <section class="products-heading">
            <div>
                <p class="products-eyebrow">KELOLA KATALOG</p>
                <h1>{{ $editing ? 'Ubah Produk' : 'Tambah Produk' }}</h1>
                <p class="products-subtitle">Lengkapi informasi produk mochi di bawah ini.</p>
            </div>
        </section>

        <section class="product-form-card">
            @if ($errors->any())
                <div class="action-notice action-notice-error" role="alert">Periksa kembali data produk yang Anda masukkan.</div>
            @endif

            <form class="product-edit-form" action="{{ $editing ? route('products.update', $product) : route('products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="form_type" value="product">
                @if ($editing)
                    @method('PUT')
                    <input type="hidden" name="edit_product_id" value="{{ $product->id_produk }}">
                @endif

                <div class="product-form-grid">
                    @if ($editing)
                        <div class="product-form-field">
                            <label for="id_produk">ID Produk</label>
                            <input id="id_produk" type="text" value="{{ $product->id_produk }}" readonly>
                        </div>
                    @endif
                    <div class="product-form-field">
                        <label for="nama_produk">Nama produk</label>
                        <input id="nama_produk" name="nama_produk" type="text" value="{{ old('nama_produk', $product->nama_produk) }}" maxlength="30" required>
                        @error('nama_produk')<span class="form-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="product-form-field">
                        <label for="id_kategori">Kategori</label>
                        <select id="id_kategori" name="id_kategori">
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id_kategori }}" @selected((string) old('id_kategori', $product->id_kategori) === (string) $category->id_kategori)>
                                    {{ $category->nama_kategori }}
                                </option>
                            @endforeach
                        </select>
                        @if ($categories->isEmpty())
                            <span class="category-id-selection">Belum ada kategori. Tambahkan kategori dari halaman daftar produk terlebih dahulu.</span>
                        @endif
                        @error('id_kategori')<span class="form-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="product-form-field">
                        <label for="harga">Harga (Rp)</label>
                        <input id="harga" name="harga" type="number" min="0" max="2147483647" step="1" value="{{ old('harga', $product->harga) }}" required>
                        @error('harga')<span class="form-field-error">{{ $message }}</span>@enderror
                    </div>
                    @if ($editing)
                        <div class="product-form-field">
                            <label for="stok">Stok <span>(dikelola di menu stok)</span></label>
                            <input id="stok" type="text" value="{{ $product->stok === null ? 'Belum diatur' : number_format((int) $product->stok, 0, ',', '.').' pcs' }}" readonly>
                        </div>
                    @endif
                    <div class="product-form-field product-image-field">
                        <label for="gambar_url">URL foto produk <span>(opsional, maks. 100 karakter)</span></label>
                        <input id="gambar_url" name="gambar_url" type="url" value="{{ old('gambar_url', '') }}" maxlength="100" placeholder="https://contoh.com/gambar.jpg">
                        @error('gambar_url')<span class="form-field-error">{{ $message }}</span>@enderror
                        <label for="gambar">atau unggah foto <span>(opsional, maks. 2 MB)</span></label>
                        <input id="gambar" name="gambar" type="file" accept="image/*">
                        @error('gambar')<span class="form-field-error">{{ $message }}</span>@enderror
                        @if ($editing && $product->gambar)
                            <div class="current-product-image">
                                <img src="{{ \Illuminate\Support\Str::startsWith($product->gambar, ['http://', 'https://']) ? $product->gambar : \Illuminate\Support\Facades\Storage::disk('public')->url($product->gambar) }}" alt="Foto {{ $product->nama_produk }}">
                                <label><input type="checkbox" name="hapus_gambar" value="1" @checked(old('hapus_gambar'))> Hapus foto saat disimpan</label>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="product-form-footer">
                    <a class="cancel-form-link" href="{{ route('products.index') }}">Batal</a>
                    <button class="filter-button" type="submit">{{ $editing ? 'Simpan perubahan' : 'Tambah produk' }}</button>
                </div>
            </form>
        </section>
    </main>
        </div>
    </div>
</body>
</html>
