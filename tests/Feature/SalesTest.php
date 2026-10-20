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
        Schema::dropIfExists('tr_customer_order_items');
        Schema::dropIfExists('tr_customer_orders');
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
            $table->dateTime('tanggal');
            $table->integer('total');
            $table->string('status', 20);
            $table->string('ms_user_id_user', 30);
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

        Schema::create('tr_customer_orders', function ($table): void {
            $table->id();
            $table->uuid('tracking_token')->unique();
            $table->string('nama_pelanggan', 60);
            $table->string('no_telepon', 20)->nullable();
            $table->unsignedInteger('total');
            $table->string('status', 20)->default('Menunggu');
            $table->unsignedInteger('sale_id')->nullable();
            $table->timestamps();
        });

        Schema::create('tr_customer_order_items', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('customer_order_id');
            $table->unsignedInteger('id_produk');
            $table->string('nama_produk', 60);
            $table->unsignedInteger('jumlah');
            $table->unsignedInteger('harga');
            $table->unsignedInteger('sub_total');
            $table->timestamps();
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

    public function test_customer_can_submit_order_and_track_status_without_reserving_stock(): void
    {
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);

        $this->post('/pesan', [
            'nama_pelanggan' => 'Dewi',
            'no_telepon' => '08123456789',
            'items' => [['id_produk' => 1, 'jumlah' => 2]],
        ])->assertRedirect();

        $order = DB::table('tr_customer_orders')->first();
        $this->assertNotNull($order);
        $this->assertDatabaseHas('tr_customer_orders', [
            'id' => $order->id,
            'nama_pelanggan' => 'Dewi',
            'total' => 30000,
            'status' => 'Menunggu',
        ]);
        $this->assertDatabaseHas('tr_customer_order_items', [
            'customer_order_id' => $order->id,
            'nama_produk' => 'Mochi Stroberi',
            'jumlah' => 2,
            'harga' => 15000,
            'sub_total' => 30000,
        ]);
        $this->assertDatabaseCount('tr_pesanan', 0);
        $this->assertDatabaseHas('ms_produk', ['id_produk' => 1, 'stok' => 8]);

        $this->get('/pesanan/'.$order->tracking_token)
            ->assertOk()
            ->assertSee('Dewi')
            ->assertSee('Menunggu konfirmasi dari kasir.')
            ->assertSee('Mochi Stroberi');

        $this->getJson('/pesanan/'.$order->tracking_token.'/status')
            ->assertOk()
            ->assertJsonPath('status', 'Menunggu')
            ->assertJsonPath('sale_id', null);
    }

    public function test_customer_order_validation_rejects_unavailable_stock(): void
    {
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 1);

        $this->from('/pesan')->post('/pesan', [
            'nama_pelanggan' => 'Dewi',
            'items' => [['id_produk' => 1, 'jumlah' => 2]],
        ])->assertRedirect('/pesan')->assertSessionHasErrors('items');

        $this->assertDatabaseCount('tr_customer_orders', 0);
    }

    public function test_cashier_can_accept_and_complete_customer_order_as_paid_sale(): void
    {
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);

        $this->post('/pesan', [
            'nama_pelanggan' => 'Dewi',
            'items' => [['id_produk' => 1, 'jumlah' => 2]],
        ]);
        $order = DB::table('tr_customer_orders')->first();

        $this->signIn('Kasir');
        $this->get('/order-pelanggan')
            ->assertOk()
            ->assertSee('Dewi')
            ->assertSee('Mochi Stroberi')
            ->assertSee('Menunggu');
        $this->getJson('/order-pelanggan/notifikasi')
            ->assertOk()
            ->assertJsonPath('pending_count', 1)
            ->assertJsonPath('latest_order_id', $order->id);

        $this->put('/order-pelanggan/'.$order->id.'/status', ['status' => 'Diproses'])
            ->assertRedirect('/order-pelanggan/'.$order->id);

        $this->post('/order-pelanggan/'.$order->id.'/selesaikan', [
            'metode' => 'Tunai',
            'jumlah_bayar' => 30000,
        ])->assertRedirect('/transaksi/1');

        $this->assertDatabaseHas('tr_customer_orders', [
            'id' => $order->id,
            'status' => 'Selesai',
            'sale_id' => 1,
        ]);
        $this->assertDatabaseHas('tr_pesanan', [
            'id_pesanan' => 1,
            'total' => 30000,
            'status' => 'Selesai',
            'ms_user_id_user' => '1',
        ]);
        $this->assertDatabaseHas('tr_pembayaran', [
            'id_pesanan' => '1',
            'metode' => 'Tunai',
            'status_pembayaran' => 'Lunas',
        ]);
        $this->assertDatabaseHas('ms_produk', ['id_produk' => 1, 'stok' => 6]);
        $this->getJson('/pesanan/'.$order->tracking_token.'/status')
            ->assertOk()
            ->assertJsonPath('status', 'Selesai')
            ->assertJsonPath('sale_id', 1);
    }

    public function test_customer_order_that_loses_stock_can_be_rejected_after_acceptance(): void
    {
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 2);

        $this->post('/pesan', [
            'nama_pelanggan' => 'Dewi',
            'items' => [['id_produk' => 1, 'jumlah' => 2]],
        ]);
        $order = DB::table('tr_customer_orders')->first();
        DB::table('ms_produk')->where('id_produk', 1)->update(['stok' => 1]);

        $this->signIn('Kasir');
        $this->put('/order-pelanggan/'.$order->id.'/status', ['status' => 'Diproses']);
        $this->from('/order-pelanggan/'.$order->id)->post('/order-pelanggan/'.$order->id.'/selesaikan', [
            'metode' => 'Tunai',
            'jumlah_bayar' => 30000,
        ])->assertRedirect('/order-pelanggan/'.$order->id)
            ->assertSessionHasErrors('items');

        $this->put('/order-pelanggan/'.$order->id.'/status', ['status' => 'Ditolak'])
            ->assertRedirect('/order-pelanggan/'.$order->id);

        $this->assertDatabaseHas('tr_customer_orders', ['id' => $order->id, 'status' => 'Ditolak']);
        $this->assertDatabaseCount('tr_pesanan', 0);
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
}
