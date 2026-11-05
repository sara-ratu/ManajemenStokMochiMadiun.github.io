<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Riwayat | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page catalog-page stock-page">
    <header class="catalog-topbar">
        <a class="catalog-brand" href="{{ route('dashboard') }}" aria-label="Beranda Mochi Madiun">
            <img class="mochi-logo" src="{{ asset('images/logo-mochi-madiun.png') }}" alt="">
            @php($historyNow = now('Asia/Jakarta'))
            <small class="catalog-datetime"><time data-live-clock datetime="{{ $historyNow->toIso8601String() }}">{{ $historyNow->locale('id')->translatedFormat('D, d M Y') }} · {{ $historyNow->format('H.i') }} WIB</time></small>
        </a>
    </header>

    <div class="products-app-shell">
        <aside class="app-sidebar">
            <p class="sidebar-section-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <a class="sidebar-nav-link" href="{{ route('dashboard') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z"/></svg>
                    <span>Beranda</span>
                </a>
                <a class="sidebar-nav-link" href="{{ route('sales.index') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h3m2 0h3m-8 4h8"/></svg>
                    <span>{{ auth()->user()->can('cashier') ? 'Transaksi' : 'Laporan Penjualan' }}</span>
                </a>
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('history.index') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9 9 0 0 0-9 9zm0 0h7m-7 0 3-3m-3 3 3 3M12 3v7m0 0 3 3m-3-3-3 3"/></svg>
                    <span>Riwayat</span>
                </a>
                @can('cashier')
                    <a class="sidebar-nav-link" href="{{ route('customer-orders.cashier.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/><path d="m8 19 4 3 4-3"/></svg>
                        <span>Order</span>
                    </a>
                    <a class="sidebar-nav-link" href="{{ route('stocks.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4z"/><path d="M4 7l2-4h12l2 4M12 11v6m-3-3h6"/></svg>
                        <span>Kelola Stok</span>
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
                <div class="topbar-page-label">Riwayat</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ auth()->user()->nama_lengkap ?: 'Pengguna' }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="logout-button" type="submit">Keluar</button>
                    </form>
                </div>
            </header>

            <main class="products-main stock-main">
    @php($range = array_filter(['start_date' => $startDate, 'end_date' => $endDate]))
    <section class="products-heading stock-heading">
        <div>
            <p class="products-eyebrow">RIWAYAT</p>
            <p class="products-subtitle">Telusuri transaksi penjualan dan perubahan stok.</p>
        </div>
    </section>

    <section class="stock-summary" aria-label="Ringkasan riwayat">
        <article class="stock-summary-card">
            <span class="stock-summary-icon stock-summary-icon-green" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M3 12h18M12 3v18"/><circle cx="12" cy="12" r="9"/></svg>
            </span>
            <div><span>Transaksi Selesai</span><strong>{{ number_format($salesCount, 0, ',', '.') }}</strong></div>
        </article>
    </section>

    <nav class="history-tabs" aria-label="Jenis riwayat">
        <a class="history-tab {{ $tab === 'penjualan' ? 'is-active' : '' }}" href="{{ route('history.index', ['tab' => 'penjualan'] + $range) }}">Penjualan</a>
        <a class="history-tab {{ $tab === 'stok' ? 'is-active' : '' }}" href="{{ route('history.index', ['tab' => 'stok'] + $range) }}">Stok</a>
    </nav>

    <section class="stock-history-card sales-history-card" aria-label="Riwayat">
        <div class="stock-history-heading">
            <div>
                @if ($tab === 'stok')
                    <h2>Riwayat Stok</h2>
                    <p>Stok masuk dan stok keluar dari penjualan.</p>
                @else
                    <h2>Riwayat Penjualan</h2>
                    <p>Semua pesanan, termasuk yang dibatalkan atau belum diproses.</p>
                @endif
            </div>
        </div>

        <form class="sales-history-filter" method="GET" action="{{ route('history.index') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="sales-history-filter-field">
                <label for="history-start-date">Dari tanggal</label>
                <input id="history-start-date" type="date" name="start_date" value="{{ $startDate }}">
            </div>
            <div class="sales-history-filter-field">
                <label for="history-end-date">Sampai tanggal</label>
                <input id="history-end-date" type="date" name="end_date" value="{{ $endDate }}">
            </div>
            @if ($tab === 'penjualan')
                <div class="sales-history-filter-field">
                    <label for="history-status">Status</label>
                    <select id="history-status" name="status">
                        <option value="">Semua status</option>
                        @foreach (['Selesai', 'Batal', 'Pending'] as $option)
                            <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="sales-history-filter-actions">
                <button class="sales-history-filter-submit" type="submit">Terapkan</button>
                @if ($startDate || $endDate || $status)
                    <a class="stock-clear-filter" href="{{ route('history.index', ['tab' => $tab]) }}">Reset</a>
                @endif
            </div>
        </form>

        <div class="catalog-table-scroll">
            @if ($tab === 'stok')
                <table class="stock-table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Produk</th>
                            <th scope="col">Jumlah</th>
                            <th scope="col">Keterangan</th>
                            <th scope="col">Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stocks as $stock)
                            <tr>
                                <td class="stock-id-cell">#{{ $stock->id_stok }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($stock->tanggal)->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="stock-product-cell">{{ $stock->nama_produk ?: 'Produk tidak ditemukan' }}</td>
                                <td>
                                    <span class="{{ (int) $stock->jumlah > 0 ? 'stock-incoming-badge' : 'stock-outgoing-badge' }}">{{ (int) $stock->jumlah > 0 ? '+' : '' }}{{ (int) $stock->jumlah }} pcs</span>
                                </td>
                                <td>{{ $stock->keterangan ?: '-' }}</td>
                                <td>{{ $stock->nama_petugas ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td class="stock-empty-cell" colspan="6">Belum ada catatan stok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="stock-table sales-history-table">
                    <thead>
                        <tr>
                            <th scope="col">ID Pesanan</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Item</th>
                            <th scope="col">Pembayaran</th>
                            <th scope="col">Total</th>
                            <th scope="col">Status</th>
                            <th scope="col">Kasir</th>
                            <th scope="col">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td class="stock-id-cell">#{{ $sale->id_pesanan }}</td>
                                <td class="sales-history-date">
                                    <strong>{{ \Illuminate\Support\Carbon::parse($sale->tanggal)->locale('id')->translatedFormat('d M Y') }}</strong>
                                    <span>{{ \Illuminate\Support\Carbon::parse($sale->tanggal)->format('H.i') }} WIB</span>
                                </td>
                                <td>{{ (int) $sale->jumlah_item }} item</td>
                                <td>{{ $sale->metode ?: 'Belum dibayar' }}</td>
                                <td>Rp{{ number_format((int) $sale->total, 0, ',', '.') }}</td>
                                <td>
                                    <span class="sale-status-badge {{ $sale->status === 'Pending' ? 'history-status-pending' : ($sale->status === 'Batal' ? 'history-status-batal' : '') }}">{{ $sale->status }}</span>
                                </td>
                                <td>{{ $sale->nama_kasir ?: '-' }}</td>
                                <td>
                                    @if ($sale->status === 'Selesai')
                                        <a class="sales-detail-link sales-history-action" href="{{ route('sales.show', $sale->id_pesanan) }}">Lihat struk</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td class="stock-empty-cell" colspan="8">Belum ada transaksi yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        @php($rows = $tab === 'stok' ? $stocks : $sales)
        @if ($rows->hasPages())
            <div class="catalog-pagination">{{ $rows->links() }}</div>
        @endif
    </section>
</main>
        </div>
    </div>
</body>
</html>
