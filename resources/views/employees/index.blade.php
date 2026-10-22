<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Data Karyawan | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page catalog-page">
    <header class="catalog-topbar">
        <a class="catalog-brand" href="{{ route('employees.index') }}" aria-label="Mochi Madiun">
            <span>MOCHI MADIUN</span>
            @php($employeeNow = now('Asia/Jakarta'))
            <small><time data-live-clock datetime="{{ $employeeNow->toIso8601String() }}">{{ $employeeNow->locale('id')->translatedFormat('l, d F Y') }} · {{ $employeeNow->format('H.i') }}</time></small>
        </a>
        <form class="catalog-search" method="GET" action="{{ route('employees.index') }}" role="search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            <input name="q" type="search" value="{{ $search }}" placeholder="Cari nama, email, telepon, atau jabatan..." maxlength="100" aria-label="Cari karyawan">
        </form>
    </header>

    <div class="products-app-shell">
        <aside class="app-sidebar">
            <p class="sidebar-section-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('employees.index') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 4a4 4 0 0 1 0 8m2 2a7 7 0 0 1 4 7"/></svg>
                    <span>Data Karyawan</span>
                </a>
                <a class="sidebar-nav-link" href="{{ route('products.index') }}">
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
                <div class="topbar-page-label">Data Karyawan</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ auth()->user()->nama_lengkap ?: 'Pengguna' }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="logout-button" type="submit">Keluar</button>
                    </form>
                </div>
            </header>

            <main class="products-main stock-main">
                <section class="products-heading stock-heading">
                    <div>
                        <p class="products-eyebrow">MANAJEMEN KARYAWAN</p>
                        <h1>Data Karyawan</h1>
                        <p class="products-subtitle">Informasi akun, peran, status, dan shift karyawan Mochi Madiun.</p>
                    </div>
                </section>

                <section class="stock-summary" aria-label="Ringkasan data karyawan">
                    <article class="stock-summary-card">
                        <span class="stock-summary-icon stock-summary-icon-pink" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 4a4 4 0 0 1 0 8m2 2a7 7 0 0 1 4 7"/></svg>
                        </span>
                        <div><span>Total Karyawan</span><strong>{{ number_format($employeeCount, 0, ',', '.') }}</strong></div>
                    </article>
                    <article class="stock-summary-card">
                        <span class="stock-summary-icon stock-summary-icon-green" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>
                        </span>
                        <div><span>Akun Aktif</span><strong>{{ number_format($activeEmployeeCount, 0, ',', '.') }}</strong></div>
                    </article>
                    <article class="stock-summary-card">
                        <span class="stock-summary-icon stock-summary-icon-purple" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h5"/></svg>
                        </span>
                        <div><span>Kasir</span><strong>{{ number_format($cashierCount, 0, ',', '.') }}</strong></div>
                    </article>
                </section>

                <section class="stock-history-card" aria-label="Daftar karyawan">
                    <div class="stock-history-heading">
                        <div>
                            <h2>Daftar Karyawan</h2>
                            <p>Data ditampilkan sesuai akun yang terdaftar di sistem.</p>
                        </div>
                        @if ($search !== '')
                            <a class="stock-clear-filter" href="{{ route('employees.index') }}">Hapus pencarian</a>
                        @endif
                    </div>
                    <div class="catalog-table-scroll">
                        <table class="stock-table">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Nama Karyawan</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">No. Telepon</th>
                                    <th scope="col">Jabatan</th>
                                    <th scope="col">Shift</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($employees as $employee)
                                    <tr>
                                        <td class="stock-id-cell">#{{ $employee->id_user }}</td>
                                        <td class="stock-product-cell">{{ $employee->nama_lengkap ?: 'Nama belum diisi' }}</td>
                                        <td>{{ $employee->email ?: '—' }}</td>
                                        <td>{{ $employee->no_telepon ?: '—' }}</td>
                                        <td>{{ $employee->role === 'SuperAdmin' ? 'Super Admin' : ($employee->role ?: 'Belum diatur') }}</td>
                                        <td>
                                            @if ($employee->shift)
                                                {{ $employee->shift->nama_shift }}
                                                @if ($employee->shift->jam_mulai && $employee->shift->jam_selesai)
                                                    · {{ \Illuminate\Support\Str::substr((string) $employee->shift->jam_mulai, 0, 5) }}–{{ \Illuminate\Support\Str::substr((string) $employee->shift->jam_selesai, 0, 5) }}
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <span class="profile-status {{ $employee->status === 'Aktif' ? 'profile-status-active' : 'profile-status-inactive' }}">
                                                <span aria-hidden="true"></span>
                                                {{ $employee->status === 'Aktif' ? 'Aktif' : ($employee->status ?: 'Belum diatur') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="stock-empty-cell" colspan="7">
                                            <span aria-hidden="true">👥</span>
                                            <strong>{{ $search !== '' ? 'Karyawan tidak ditemukan' : 'Belum ada data karyawan' }}</strong>
                                            <span>{{ $search !== '' ? 'Coba kata pencarian lain.' : 'Akun karyawan yang terdaftar akan tampil di sini.' }}</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($employees->hasPages())
                        <div class="catalog-pagination">{{ $employees->links() }}</div>
                    @endif
                </section>
            </main>
        </div>
    </div>
</body>
</html>
