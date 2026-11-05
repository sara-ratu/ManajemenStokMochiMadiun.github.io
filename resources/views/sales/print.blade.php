<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Penjualan | Mochi Madiun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="sales-report-print-page">
    <main class="sales-report-print">
        <header class="sales-report-print-header">
            <div>
                <div class="sales-report-brand">
                    <img src="{{ asset('images/logo-mochi-madiun.png') }}" alt="">
                    <div>
                        <p class="products-eyebrow">MOCHI MADIUN</p>
                        <h1>Laporan Penjualan</h1>
                    </div>
                </div>
                <dl class="sales-report-meta">
                    <div>
                        <dt>Periode laporan</dt>
                        <dd>
                            @if ($startDate && $endDate)
                                {{ \Illuminate\Support\Carbon::parse($startDate)->locale('id')->translatedFormat('d F Y') }}
                                – {{ \Illuminate\Support\Carbon::parse($endDate)->locale('id')->translatedFormat('d F Y') }}
                            @else
                                Semua periode
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Tanggal cetak</dt>
                        <dd>{{ now('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H.i') }} WIB</dd>
                    </div>
                </dl>
            </div>
            <div class="sales-report-print-actions">
                <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
            </div>
        </header>

        <section class="sales-report-print-summary" aria-label="Ringkasan laporan">
            <div><span>Transaksi selesai</span><strong>{{ number_format($salesCount, 0, ',', '.') }}</strong></div>
            <div><span>Total penjualan</span><strong>Rp{{ number_format($salesTotal, 0, ',', '.') }}</strong></div>
        </section>

        <table class="sales-report-print-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>ID Pesanan</th>
                    <th>Tanggal</th>
                    <th>Kasir</th>
                    <th class="sales-report-print-amount">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>#{{ $sale->id_pesanan }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($sale->tanggal)->locale('id')->translatedFormat('d M Y, H.i') }}</td>
                        <td>{{ $sale->nama_kasir ?: 'Pengguna #'.$sale->ms_user_id_user }}</td>
                        <td class="sales-report-print-amount">Rp{{ number_format((int) $sale->total, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="sales-report-print-empty">Tidak ada transaksi pada rentang tanggal ini.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4">Total {{ number_format($salesCount, 0, ',', '.') }} transaksi</th>
                    <th class="sales-report-print-amount">Rp{{ number_format($salesTotal, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
    </main>
</body>
</html>
