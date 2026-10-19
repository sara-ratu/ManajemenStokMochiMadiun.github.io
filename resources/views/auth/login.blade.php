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
            <svg class="brand-mascot" viewBox="0 0 100 92" role="img" aria-labelledby="mascot-title">
                <title id="mascot-title">Maskot Mochi Madiun</title>
                <path d="M26 20c-7-8 3-15 10-11 4-10 15-8 18-3 7-6 17-1 17 6 9-1 13 8 8 14l-8 5H29z" fill="#fff8ef" stroke="#76534a" stroke-width="2.5" stroke-linejoin="round"/>
                <path d="M32 18c4 4 7 1 10 0 4 4 8 3 12 0 4 4 9 3 13 0 4 3 7 2 10-1" fill="none" stroke="#d8b9a8" stroke-width="2" stroke-linecap="round"/>
                <path d="M19 51c1-20 13-31 31-31s30 11 31 31l-4 20c-2 9-12 13-27 13S25 80 23 71z" fill="#fff1df" stroke="#76534a" stroke-width="2.5"/>
                <path d="M20 54c10-4 18-2 29-1 13 1 22-4 32-2v10c-9-2-19 5-32 4-12-1-20-5-30-2z" fill="#ff8ca9" stroke="#76534a" stroke-width="2"/>
                <path d="M34 48a3 3 0 1 0 0 .1M66 48a3 3 0 1 0 0 .1" fill="#76534a" stroke="#76534a" stroke-width="4" stroke-linecap="round"/>
                <path d="M45 55c2 5 8 5 10 0" fill="none" stroke="#76534a" stroke-width="2" stroke-linecap="round"/>
                <path d="M30 51c-4-2-7-1-9 1m49-1c4-2 7-1 9 1" fill="none" stroke="#ef7894" stroke-width="3" stroke-linecap="round"/>
                <path d="M74 27c3-8 9-11 14-8-1 6-5 10-12 12m-3-2c-1-8 2-13 7-14 3 5 2 10-4 15" fill="#91c95c" stroke="#527c3f" stroke-width="1.5"/>
                <path d="M37 82h27" stroke="#76534a" stroke-width="2" stroke-linecap="round"/>
                </svg>
            <p class="brand-name">MOCHI MADIUN</p>
        </header>

        @auth
            <section class="login-success" aria-live="polite">
                <h1>Berhasil masuk!</h1>
                <p>Halo, {{ auth()->user()->nama_lengkap }}. Anda sudah masuk ke akun Mochi Madiun.</p>
                <a class="login-button login-product-link" href="{{ route('products.index') }}">Lihat produk</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="login-button" type="submit">Keluar</button>
                </form>
            </section>
        @else
            <form class="login-form" action="{{ route('login.store') }}" method="POST">
                @csrf

                <div class="form-field">
                    <label for="username">Username</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        value="{{ old('username') }}"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                        autofocus
                        aria-describedby="{{ $errors->has('username') ? 'username-error' : '' }}"
                    >
                    @error('username')
                        <p class="field-error" id="username-error">{{ $message }}</p>
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
        @endauth
    </main>
</body>
</html>
