<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Profil | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="products-page catalog-page">
    <header class="catalog-topbar">
        <a class="catalog-brand" href="{{ route('dashboard') }}" aria-label="Beranda Mochi Madiun">
            <img class="mochi-logo" src="{{ asset('images/mochi-madiun-logo.png') }}" alt="">
            @php($profileNow = now('Asia/Jakarta'))
            <small><time data-live-clock datetime="{{ $profileNow->toIso8601String() }}">{{ $profileNow->locale('id')->translatedFormat('l, d F Y') }} · {{ $profileNow->format('H.i') }}</time></small>
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
                <a class="sidebar-nav-link sidebar-nav-link-active" href="{{ route('profile.show') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <span>Profil</span>
                </a>
            </nav>
            <div class="sidebar-footer">Mochi Madiun <span>Manajemen Toko</span></div>
        </aside>

        <div class="products-workspace">
            <header class="products-topbar">
                <div class="topbar-page-label">Profil Saya</div>
                <div class="products-account">
                    <span class="account-greeting">Halo, <strong>{{ $user->nama_lengkap ?: 'Pengguna' }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="logout-button" type="submit">Keluar</button>
                    </form>
                </div>
            </header>

    <main class="products-main profile-main">
        <a class="profile-back-link" href="{{ route('dashboard') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            Kembali ke beranda
        </a>

        <section class="profile-heading">
            <div>
                <p class="products-eyebrow">AKUN SAYA</p>
                <h1>Profil Saya</h1>
                <p class="products-subtitle">Perbarui informasi pribadi dan keamanan akun Anda.</p>
            </div>
        </section>

        @if (session('status'))
            <div class="action-notice action-notice-success" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="action-notice action-notice-error" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="action-notice action-notice-error" role="alert">Periksa kembali data yang Anda masukkan.</div>
        @endif

        <section class="profile-layout" aria-label="Informasi profil pengguna">
            <aside class="profile-summary">
                <div class="profile-avatar" aria-hidden="true">
                    {{ $user->nama_lengkap ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->nama_lengkap, 0, 1)) : '?' }}
                </div>
                <h2>{{ $user->nama_lengkap ?: 'Nama belum diisi' }}</h2>
                <p class="profile-role">{{ $user->role === 'SuperAdmin' ? 'Administrator' : ($user->role === 'Kasir' ? 'Kasir' : ($user->role ?: 'Peran belum diatur')) }}</p>
                <span class="profile-status {{ $user->status === 'Aktif' ? 'profile-status-active' : 'profile-status-inactive' }}">
                    <span aria-hidden="true"></span>
                    {{ $user->status === 'Aktif' ? 'Akun aktif' : ($user->status === 'TidakAktif' ? 'Akun tidak aktif' : 'Status belum diatur') }}
                </span>
            </aside>

            <div class="profile-details">
                <div class="profile-details-heading">
                    <div class="profile-details-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    </div>
                    <div>
                        <h2>Informasi pribadi</h2>
                        <p>Detail profil akun Anda.</p>
                    </div>
                </div>

                <form class="profile-edit-form" action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="profile-form-grid">
                        <div class="profile-form-field">
                            <label for="nama_lengkap">Nama lengkap</label>
                            <input id="nama_lengkap" name="nama_lengkap" type="text" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" maxlength="30" autocomplete="name" required>
                            @error('nama_lengkap')<span class="form-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="profile-form-field">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="45" autocomplete="email" required>
                            @error('email')<span class="form-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="profile-form-field">
                            <label for="no_telepon">Nomor telepon <span>(opsional)</span></label>
                            <input id="no_telepon" name="no_telepon" type="tel" value="{{ old('no_telepon', $user->no_telepon) }}" maxlength="15" autocomplete="tel">
                            @error('no_telepon')<span class="form-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="profile-form-field">
                            <label for="alamat">Alamat <span>(opsional)</span></label>
                            <input id="alamat" name="alamat" type="text" value="{{ old('alamat', $user->alamat) }}" maxlength="100" autocomplete="street-address">
                            @error('alamat')<span class="form-field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="profile-readonly-info">
                        <div><span>Peran</span><strong>{{ $user->role === 'SuperAdmin' ? 'Administrator' : ($user->role === 'Kasir' ? 'Kasir' : ($user->role ?: 'Belum diatur')) }}</strong></div>
                        <div><span>Shift</span><strong>{{ $user->shift?->nama_shift ?: 'Belum ditentukan' }}</strong></div>
                        @if ($user->shift?->jam_mulai && $user->shift?->jam_selesai)
                            <div><span>Jam shift</span><strong>{{ \Illuminate\Support\Str::substr((string) $user->shift->jam_mulai, 0, 5) }}–{{ \Illuminate\Support\Str::substr((string) $user->shift->jam_selesai, 0, 5) }}</strong></div>
                        @endif
                    </div>

                    @if ($user->role === 'Kasir')
                        <section class="profile-shift-request" aria-label="Pengajuan perubahan shift">
                            <div class="profile-password-heading">
                                <h3>Pengajuan perubahan shift</h3>
                                <p>Pengajuan akan diterapkan setelah disetujui administrator.</p>
                            </div>
                            @if ($latestShiftRequest?->status === \App\Models\ShiftChangeRequest::STATUS_PENDING)
                                <div class="profile-shift-request-status" role="status">
                                    Pengajuan shift {{ $latestShiftRequest->shift?->nama_shift ?: 'pilihan Anda' }} sedang menunggu persetujuan administrator.
                                </div>
                            @else
                                @if ($latestShiftRequest)
                                    <div class="profile-shift-request-status profile-shift-request-status-{{ $latestShiftRequest->status === \App\Models\ShiftChangeRequest::STATUS_APPROVED ? 'approved' : 'rejected' }}" role="status">
                                        Pengajuan shift {{ $latestShiftRequest->shift?->nama_shift ?: 'sebelumnya' }} {{ $latestShiftRequest->status === \App\Models\ShiftChangeRequest::STATUS_APPROVED ? 'telah disetujui' : 'ditolak' }}.
                                    </div>
                                @endif
                                <form class="profile-shift-request-form" action="{{ route('shift-requests.store') }}" method="POST">
                                    @csrf
                                    <div class="profile-form-field">
                                        <label for="request_shift_id">Pilih shift yang diajukan</label>
                                        <select id="request_shift_id" name="id_shift" required>
                                            <option value="">Pilih shift</option>
                                            @foreach ($requestableShifts as $shift)
                                                <option value="{{ $shift->id_shift }}" @selected((string) old('id_shift') === (string) $shift->id_shift) @disabled((int) $user->id_shift === (int) $shift->id_shift)>
                                                    {{ $shift->nama_shift }}{{ $shift->jam_mulai && $shift->jam_selesai ? ' · '.\Illuminate\Support\Str::substr((string) $shift->jam_mulai, 0, 5).'–'.\Illuminate\Support\Str::substr((string) $shift->jam_selesai, 0, 5) : '' }}{{ (int) $user->id_shift === (int) $shift->id_shift ? ' (shift saat ini)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('id_shift')<span class="form-field-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="profile-shift-request-footer">
                                        <button class="filter-button" type="submit" @disabled($requestableShifts->every(fn ($shift) => (int) $shift->id_shift === (int) $user->id_shift))>Ajukan perubahan shift</button>
                                    </div>
                                </form>
                            @endif
                        </section>
                    @endif

                    <div class="profile-password-heading">
                        <h3>Ganti password</h3>
                        <p>Kosongkan jika tidak ingin mengganti password.</p>
                    </div>
                    <div class="profile-form-grid">
                        <div class="profile-form-field">
                            <label for="current_password">Password saat ini</label>
                            <input id="current_password" name="current_password" type="password" autocomplete="current-password">
                            @error('current_password')<span class="form-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="profile-form-field">
                            <label for="password">Password baru</label>
                            <input id="password" name="password" type="password" autocomplete="new-password">
                            @error('password')<span class="form-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="profile-form-field profile-confirm-password">
                            <label for="password_confirmation">Konfirmasi password baru</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="profile-form-footer">
                        <button class="filter-button" type="submit">Simpan perubahan</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
        </div>
    </div>
</body>
</html>
