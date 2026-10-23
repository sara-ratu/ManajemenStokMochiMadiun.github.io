<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('tr_pembayaran');
        Schema::dropIfExists('tr_detail_pesanan');
        Schema::dropIfExists('tr_pesanan');
        Schema::dropIfExists('tr_stok');
        Schema::dropIfExists('ms_produk');
        Schema::dropIfExists('ms_kategori');
        Schema::dropIfExists('ms_user');

        Schema::create('ms_user', function ($table): void {
            $table->integer('id_user')->primary();
            $table->unsignedInteger('id_shift')->nullable();
            $table->string('nama_lengkap', 30)->nullable();
            $table->string('email', 45)->nullable();
            $table->string('password', 60)->nullable();
            $table->string('no_telepon', 15)->nullable();
            $table->string('alamat', 100)->nullable();
            $table->string('status', 12)->nullable();
            $table->string('role', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('ms_kategori', function ($table): void {
            $table->integer('id_kategori')->primary();
            $table->string('nama_kategori', 30)->nullable();
        });

        Schema::create('ms_produk', function ($table): void {
            $table->integer('id_produk')->primary();
            $table->integer('id_kategori')->nullable();
            $table->string('nama_produk', 30)->nullable();
            $table->integer('harga')->nullable();
            $table->integer('stok')->nullable();
            $table->string('gambar', 100)->nullable();
        });

        Schema::create('tr_pesanan', function ($table): void {
            $table->integer('id_pesanan')->primary();
            $table->dateTime('tanggal')->nullable();
            $table->integer('total')->nullable();
            $table->enum('status', ['Selesai', 'Pending', 'Batal'])->nullable();
            $table->string('ms_user_id_user', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('tr_detail_pesanan', function ($table): void {
            $table->integer('id_detail')->primary();
            $table->string('id_pesanan', 15);
            $table->string('id_produk', 10);
            $table->integer('jumlah');
            $table->integer('harga');
            $table->integer('sub_total');
        });

        Schema::create('tr_pembayaran', function ($table): void {
            $table->integer('id_pembayaran')->primary();
            $table->string('id_pesanan', 15);
            $table->string('metode', 20);
            $table->integer('jumlah');
            $table->string('status_pembayaran', 25);
            $table->string('waktu_bayar', 30);
        });

        Schema::create('tr_stok', function ($table): void {
            $table->integer('id_stok')->primary();
            $table->string('id_produk', 45)->nullable();
            $table->integer('id_kategori')->nullable();
            $table->date('tanggal');
            $table->integer('jumlah');
            $table->string('keterangan', 45)->nullable();
            $table->string('ms_user_id_user', 30);
            $table->string('ms_produk_id_produk', 45);
        });

    }

    public function test_guest_is_redirected_from_sales_module(): void
    {
        $this->get('/transaksi')->assertRedirect('/login');
    }

    public function test_cashier_can_view_product_picker_and_sales_history(): void
    {
        $this->signIn('Kasir');
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);

        $this->get('/transaksi')
            ->assertOk()
            ->assertSee('Transaksi Penjualan')
            ->assertSee('Pilih Produk')
            ->assertSee('Mochi Stroberi')
            ->assertSee('Rp15.000')
            ->assertSee('Keranjang')
            ->assertSee('Metode Pembayaran')
            ->assertSee('Riwayat Transaksi')
            ->assertSee('sales-history-table', false)
            ->assertSee('ID Pesanan')
            ->assertSee('Kasir');
    }

    public function test_super_admin_can_view_sales_reports_but_cannot_checkout(): void
    {
        $this->signIn('SuperAdmin');

        $this->get('/transaksi')
            ->assertOk()
            ->assertSee('Laporan Penjualan')
            ->assertSee('Riwayat Transaksi')
            ->assertSee('sales-history-count', false)
            ->assertDontSee('data-sales-checkout', false);

        $this->post('/transaksi', [])->assertForbidden();
    }

    public function test_sales_history_can_be_filtered_by_a_custom_date_range(): void
    {
        $this->signIn('SuperAdmin');
        $this->createSale(1, \Illuminate\Support\Carbon::parse('2026-10-23 12:00:00'));
        $this->createSale(2, \Illuminate\Support\Carbon::parse('2026-10-22 12:00:00'));
        $this->createSale(3, \Illuminate\Support\Carbon::parse('2026-10-19 12:00:00'));

        $this->get('/transaksi?start_date=2026-10-22&end_date=2026-10-23')
            ->assertOk()
            ->assertSee('#1')
            ->assertSee('#2')
            ->assertDontSee('#3')
            ->assertSee('2 transaksi');

        $this->get('/transaksi')
            ->assertOk()
            ->assertSee('#1')
            ->assertSee('#2')
            ->assertSee('#3')
            ->assertSee('3 transaksi');
    }

    public function test_custom_sales_history_range_requires_valid_ordered_dates(): void
    {
        $this->signIn('SuperAdmin');

        $this->get('/transaksi?start_date=2026-10-22')
            ->assertRedirect()
            ->assertSessionHasErrors('end_date');

        $this->get('/transaksi?start_date=2026-10-24&end_date=2026-10-23')
            ->assertRedirect()
            ->assertSessionHasErrors('end_date');
    }

    public function test_customer_can_submit_order_and_track_status_without_reserving_stock(): void
    {
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);

        $response = $this->post('/pesan', [
            'items' => [['id_produk' => 1, 'jumlah' => 2]],
        ])->assertRedirect();

        $order = DB::table('tr_pesanan')->where('status', 'Pending')->first();
        $this->assertNotNull($order);
        $this->assertDatabaseHas('tr_pesanan', [
            'id_pesanan' => $order->id_pesanan,
            'total' => 30000,
            'status' => 'Pending',
            'ms_user_id_user' => null,
        ]);
        $this->assertDatabaseHas('tr_detail_pesanan', [
            'id_pesanan' => (string) $order->id_pesanan,
            'id_produk' => '1',
            'jumlah' => 2,
            'harga' => 15000,
            'sub_total' => 30000,
        ]);
        $this->assertDatabaseHas('ms_produk', ['id_produk' => 1, 'stok' => 8]);

        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/pesanan/'.$order->id_pesanan.'?', $location);

        $trackResponse = $this->get($location)
            ->assertOk()
            ->assertSee('PESANAN #'.$order->id_pesanan)
            ->assertSee('Menunggu konfirmasi dari kasir.')
            ->assertSee('Mochi Stroberi');

        preg_match('/data-status-url="([^"]+)"/', $trackResponse->getContent(), $matches);
        $statusUrl = html_entity_decode($matches[1] ?? '');
        $this->assertNotSame('', $statusUrl);
        $this->getJson($statusUrl)
            ->assertOk()
            ->assertJsonPath('status', 'Pending');
    }

    public function test_customer_order_validation_rejects_unavailable_stock(): void
    {
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 1);

        $this->from('/pesan')->post('/pesan', [
            'items' => [['id_produk' => 1, 'jumlah' => 2]],
        ])->assertRedirect('/pesan')->assertSessionHasErrors('items');

        $this->assertDatabaseCount('tr_pesanan', 0);
    }

    public function test_cashier_can_accept_and_complete_customer_order_as_paid_sale(): void
    {
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);

        $this->post('/pesan', [
            'items' => [['id_produk' => 1, 'jumlah' => 2]],
        ]);
        $order = DB::table('tr_pesanan')->where('status', 'Pending')->first();

        $this->signIn('Kasir');
        $this->get('/order-pelanggan')
            ->assertOk()
            ->assertSee('Mochi Stroberi')
            ->assertSee('Order #'.$order->id_pesanan);
        $this->getJson('/order-pelanggan/notifikasi')
            ->assertOk()
            ->assertJsonPath('pending_count', 1)
            ->assertJsonPath('latest_order_id', $order->id_pesanan);

        $this->post('/order-pelanggan/'.$order->id_pesanan.'/selesaikan', [
            'metode' => 'Tunai',
            'jumlah_bayar' => 30000,
        ])->assertRedirect('/transaksi/'.$order->id_pesanan);

        $this->assertDatabaseHas('tr_pesanan', [
            'id_pesanan' => $order->id_pesanan,
            'status' => 'Selesai',
            'total' => 30000,
            'ms_user_id_user' => '1',
        ]);
        $this->assertDatabaseHas('tr_pembayaran', [
            'id_pesanan' => (string) $order->id_pesanan,
            'metode' => 'Tunai',
            'status_pembayaran' => 'Lunas',
        ]);
        $this->assertDatabaseHas('ms_produk', ['id_produk' => 1, 'stok' => 6]);
    }

    public function test_customer_order_that_loses_stock_can_be_cancelled_before_payment(): void
    {
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 2);

        $this->post('/pesan', ['items' => [['id_produk' => 1, 'jumlah' => 2]]]);
        $order = DB::table('tr_pesanan')->where('status', 'Pending')->first();
        DB::table('ms_produk')->where('id_produk', 1)->update(['stok' => 1]);

        $this->signIn('Kasir');
        $this->from('/order-pelanggan/'.$order->id_pesanan)->post('/order-pelanggan/'.$order->id_pesanan.'/selesaikan', [
            'metode' => 'Tunai',
            'jumlah_bayar' => 30000,
        ])->assertRedirect('/order-pelanggan/'.$order->id_pesanan)
            ->assertSessionHasErrors('items');

        $this->post('/order-pelanggan/'.$order->id_pesanan.'/batal')
            ->assertRedirect('/order-pelanggan');

        $this->assertDatabaseHas('tr_pesanan', ['id_pesanan' => $order->id_pesanan, 'status' => 'Batal']);
        $this->assertDatabaseCount('tr_pembayaran', 0);
    }

    public function test_customer_can_only_submit_and_cannot_access_cashier_order_queue(): void
    {
        $this->get('/pesan')->assertOk()->assertSee('Pilih Mochi Favoritmu');
        $this->get('/order-pelanggan')->assertRedirect('/login');

        $this->signIn('SuperAdmin');
        $this->get('/order-pelanggan')->assertForbidden();
    }

    public function test_cashier_can_checkout_products_and_receives_a_receipt(): void
    {
        $this->signIn('Kasir');
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);
        $this->createProduct(2, 1, 'Mochi Cokelat', 10000, 5);

        $this->post('/transaksi', [
            'items' => [
                ['id_produk' => 1, 'jumlah' => 2],
                ['id_produk' => 2, 'jumlah' => 1],
            ],
            'metode' => 'Tunai',
            'jumlah_bayar' => 50000,
        ])->assertRedirect('/transaksi/1')->assertSessionHas('status');

        $this->assertDatabaseHas('tr_pesanan', [
            'id_pesanan' => 1,
            'total' => 40000,
            'status' => 'Selesai',
            'ms_user_id_user' => '1',
        ]);
        $this->assertDatabaseHas('tr_detail_pesanan', [
            'id_detail' => 1,
            'id_pesanan' => '1',
            'id_produk' => '1',
            'jumlah' => 2,
            'harga' => 15000,
            'sub_total' => 30000,
        ]);
        $this->assertDatabaseHas('tr_detail_pesanan', [
            'id_detail' => 2,
            'id_pesanan' => '1',
            'id_produk' => '2',
            'jumlah' => 1,
            'harga' => 10000,
            'sub_total' => 10000,
        ]);
        $this->assertDatabaseHas('tr_pembayaran', [
            'id_pembayaran' => 1,
            'id_pesanan' => '1',
            'metode' => 'Tunai',
            'jumlah' => 50000,
            'status_pembayaran' => 'Lunas',
        ]);
        $this->assertDatabaseHas('ms_produk', ['id_produk' => 1, 'stok' => 6]);
        $this->assertDatabaseHas('ms_produk', ['id_produk' => 2, 'stok' => 4]);
        $this->assertDatabaseHas('tr_stok', [
            'id_stok' => 1,
            'id_produk' => '1',
            'jumlah' => -2,
            'keterangan' => 'Penjualan #1',
        ]);

        $this->get('/transaksi/1')
            ->assertOk()
            ->assertSee('TRANSAKSI BERHASIL')
            ->assertSee('Mochi Stroberi')
            ->assertSee('Rp40.000')
            ->assertSee('Rp10.000')
            ->assertSee('Rp50.000')
            ->assertSee('Cetak Struk');

        $this->get('/transaksi')
            ->assertOk()
            ->assertSee('sales-history-id', false)
            ->assertSee('#1')
            ->assertSee('Mochi Kasir')
            ->assertSee('Rp40.000')
            ->assertSee('Lihat struk');
    }

    public function test_insufficient_stock_rejects_whole_checkout_without_partial_rows(): void
    {
        $this->signIn('Kasir');
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 1);
        $this->createProduct(2, 1, 'Mochi Cokelat', 10000, 5);

        $this->from('/transaksi')->post('/transaksi', [
            'items' => [
                ['id_produk' => 1, 'jumlah' => 2],
                ['id_produk' => 2, 'jumlah' => 1],
            ],
            'metode' => 'Tunai',
            'jumlah_bayar' => 50000,
        ])->assertRedirect('/transaksi')->assertSessionHasErrors('items');

        $this->assertDatabaseCount('tr_pesanan', 0);
        $this->assertDatabaseCount('tr_detail_pesanan', 0);
        $this->assertDatabaseCount('tr_pembayaran', 0);
        $this->assertDatabaseCount('tr_stok', 0);
        $this->assertDatabaseHas('ms_produk', ['id_produk' => 1, 'stok' => 1]);
        $this->assertDatabaseHas('ms_produk', ['id_produk' => 2, 'stok' => 5]);
    }

    public function test_checkout_rejects_payment_below_total(): void
    {
        $this->signIn('Kasir');
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);

        $this->from('/transaksi')->post('/transaksi', [
            'items' => [['id_produk' => 1, 'jumlah' => 2]],
            'metode' => 'Tunai',
            'jumlah_bayar' => 20000,
        ])->assertRedirect('/transaksi')->assertSessionHasErrors('jumlah_bayar');

        $this->assertDatabaseCount('tr_pesanan', 0);
        $this->assertDatabaseHas('ms_produk', ['id_produk' => 1, 'stok' => 8]);
    }

    public function test_checkout_rejects_duplicate_product_and_invalid_payment_method(): void
    {
        $this->signIn('Kasir');
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);

        $this->from('/transaksi')->post('/transaksi', [
            'items' => [
                ['id_produk' => 1, 'jumlah' => 1],
                ['id_produk' => 1, 'jumlah' => 1],
            ],
            'metode' => 'Bitcoin',
            'jumlah_bayar' => 30000,
        ])->assertRedirect('/transaksi')
            ->assertSessionHasErrors(['items.1.id_produk', 'metode']);

        $this->assertDatabaseCount('tr_pesanan', 0);
    }

    private function signIn(string $role = 'Kasir'): void
    {
        $user = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Mochi Kasir',
            'email' => 'kasir@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => $role,
        ]);

        $this->actingAs($user);
    }

    private function createCategory(int $id, string $name): void
    {
        Schema::getConnection()->table('ms_kategori')->insert([
            'id_kategori' => $id,
            'nama_kategori' => $name,
        ]);
    }

    private function createProduct(int $id, int $categoryId, string $name, int $price, int $stock): void
    {
        Schema::getConnection()->table('ms_produk')->insert([
            'id_produk' => $id,
            'id_kategori' => $categoryId,
            'nama_produk' => $name,
            'harga' => $price,
            'stok' => $stock,
        ]);
    }

    private function createSale(int $id, \Illuminate\Support\Carbon $date): void
    {
        DB::table('tr_pesanan')->insert([
            'id_pesanan' => $id,
            'tanggal' => $date->toDateTimeString(),
            'total' => 10000,
            'status' => 'Selesai',
            'ms_user_id_user' => '1',
        ]);
    }
}
