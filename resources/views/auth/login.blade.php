<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Masuk | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
    <main class="login-card">
        <header class="login-brand">
            <img class="login-brand-logo" src="{{ asset('images/logo-mochi-madiun.png') }}" alt="Logo MOCHI MADIUN — Kualitas Premium, sejak 2019">
        </header>

        @auth
            <section class="login-success" aria-live="polite">
                <h1>Berhasil masuk!</h1>
                <p>Halo, {{ auth()->user()->nama_lengkap }}. Anda sudah masuk ke akun Mochi Madiun.</p>
                <a class="login-button login-product-link" href="{{ route('dashboard') }}">Buka dashboard</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="login-button" type="submit">Keluar</button>
                </form>
            </section>
        @else
            <form class="login-form" action="{{ route('login.store') }}" method="POST">
                @csrf

                <div class="form-field">
                    <label for="username">Nama pengguna</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        value="{{ old('username') }}"
                        placeholder="Masukkan nama pengguna"
                        autocomplete="username"
                        required
                        autofocus
                        @error('email') aria-describedby="email-error" @enderror
                    >
                    @error('email')
                        <p class="field-error" id="email-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field password-field">
                    <label for="password">Password</label>
                    <div class="password-input-wrap">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                            aria-describedby="{{ $errors->has('password') ? 'password-error' : '' }}"
                        >
                        <button class="password-toggle" type="button" aria-label="Tampilkan password" aria-pressed="false" data-password-toggle>
                            <svg class="eye-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="field-error" id="password-error">{{ $message }}</p>
                    @enderror
                </div>

                <button class="login-button" type="submit">Masuk</button>
            </form>
            <a class="customer-order-login-link" href="{{ route('customer-orders.create') }}">Pesan sebagai pelanggan</a>
        @endauth
    </main>
</body>
</html>
