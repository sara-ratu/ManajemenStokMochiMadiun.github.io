<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $editing ? 'Ubah Karyawan' : 'Tambah Karyawan' }} | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page catalog-page">
    <header class="catalog-topbar">
        <a class="catalog-brand" href="{{ route('dashboard') }}" aria-label="Beranda Mochi Madiun">
            <img class="mochi-logo" src="{{ asset('images/mochi-madiun-logo.png') }}" alt="">
            @php($employeeNow = now('Asia/Jakarta'))
            <small><time data-live-clock datetime="{{ $employeeNow->toIso8601String() }}">{{ $employeeNow->locale('id')->translatedFormat('l, d F Y') }} · {{ $employeeNow->format('H.i') }}</time></small>
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
                <div class="topbar-page-label">{{ $editing ? 'Ubah Karyawan' : 'Tambah Karyawan' }}</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ auth()->user()->nama_lengkap ?: 'Administrator' }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="logout-button" type="submit">Keluar</button>
                    </form>
                </div>
            </header>

            <main class="products-main product-form-main">
                <a class="profile-back-link" href="{{ route('employees.index') }}">‹ Kembali ke data karyawan</a>
                <section class="products-heading">
                    <div>
                        <p class="products-eyebrow">MANAJEMEN KARYAWAN</p>
                        <h1>{{ $editing ? 'Ubah Data Karyawan' : 'Tambah Karyawan' }}</h1>
                        <p class="products-subtitle">Kelola akun kasir dan informasi jadwal kerjanya.</p>
                    </div>
                </section>

                <section class="product-form-card">
                    @if ($errors->any())
                        <div class="action-notice action-notice-error" role="alert">Periksa kembali data karyawan yang Anda masukkan.</div>
                    @endif

                    <form class="product-edit-form" action="{{ $editing ? route('employees.update', $employee->id_user) : route('employees.store') }}" method="POST">
                        @csrf
                        @if ($editing)
                            @method('PUT')
                        @endif

                        <div class="product-form-grid">
                            @if ($editing)
                                <div class="product-form-field">
                                    <label for="id_user">ID Karyawan</label>
                                    <input id="id_user" type="text" value="{{ $employee->id_user }}" readonly>
                                </div>
                            @endif
                            <div class="product-form-field">
                                <label for="nama_lengkap">Nama pengguna</label>
                                <input id="nama_lengkap" name="nama_lengkap" type="text" value="{{ old('nama_lengkap', $employee->nama_lengkap) }}" maxlength="30" autocomplete="name" required>
                                @error('nama_lengkap')<span class="form-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="product-form-field">
                                <label for="email">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email', $employee->email) }}" maxlength="45" autocomplete="email" required>
                                @error('email')<span class="form-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="product-form-field">
                                <label for="no_telepon">Nomor telepon <span>(opsional)</span></label>
                                <input id="no_telepon" name="no_telepon" type="tel" value="{{ old('no_telepon', $employee->no_telepon) }}" maxlength="15" autocomplete="tel">
                                @error('no_telepon')<span class="form-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="product-form-field">
                                <label for="id_shift">Shift <span>(opsional)</span></label>
                                <select id="id_shift" name="id_shift">
                                    <option value="">Belum ditentukan</option>
                                    @foreach ($shifts as $shift)
                                        <option value="{{ $shift->id_shift }}" @selected((string) old('id_shift', $employee->id_shift) === (string) $shift->id_shift)>
                                            {{ $shift->nama_shift }}{{ $shift->jam_mulai && $shift->jam_selesai ? ' · '.\Illuminate\Support\Str::substr((string) $shift->jam_mulai, 0, 5).'–'.\Illuminate\Support\Str::substr((string) $shift->jam_selesai, 0, 5) : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_shift')<span class="form-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="product-form-field">
                                <label for="status">Status akun</label>
                                <select id="status" name="status" required>
                                    <option value="Aktif" @selected(old('status', $employee->status ?: 'Aktif') === 'Aktif')>Aktif</option>
                                    <option value="Tidak Aktif" @selected(old('status', $employee->status) === 'Tidak Aktif')>Tidak Aktif</option>
                                </select>
                                @error('status')<span class="form-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="product-form-field">
                                <label for="alamat">Alamat <span>(opsional)</span></label>
                                <input id="alamat" name="alamat" type="text" value="{{ old('alamat', $employee->alamat) }}" maxlength="100" autocomplete="street-address">
                                @error('alamat')<span class="form-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="product-form-field">
                                <label for="password">Password {{ $editing ? '(kosongkan jika tidak diubah)' : '' }}</label>
                                <input id="password" name="password" type="password" minlength="8" maxlength="60" autocomplete="new-password" @required(! $editing)>
                                @error('password')<span class="form-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="product-form-field">
                                <label for="role">Jabatan</label>
                                <input id="role" type="text" value="Kasir" readonly>
                            </div>
                        </div>

                        <div class="product-form-footer">
                            <a class="cancel-form-link" href="{{ route('employees.index') }}">Batal</a>
                            <button class="filter-button" type="submit">{{ $editing ? 'Simpan perubahan' : 'Tambah karyawan' }}</button>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
