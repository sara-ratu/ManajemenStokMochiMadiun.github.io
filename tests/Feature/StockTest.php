<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_guest_is_redirected_to_login_from_stock_module(): void
    {
        $this->get('/stok')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_stock_history_and_current_stock_summary(): void
    {
        $this->signIn();
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 12);
        $this->createStockEntry(1, 1, 1, 'Mochi Admin');

        $this->get('/stok')
            ->assertOk()
            ->assertSee('<div class="topbar-page-label">Kelola Stok</div>', false)
            ->assertSee('Catat stok masuk dan pantau riwayat persediaan produk.')
            ->assertDontSee('<h1>Kelola Stok</h1>', false)
            ->assertSee('Total Stok Saat Ini')
            ->assertSee('12 <small>pcs</small>', false)
            ->assertSee('Mochi Stroberi')
            ->assertSee('Mochi Klasik')
            ->assertSee('Produksi pagi')
            ->assertSee('Mochi Admin')
            ->assertSee('Catat Stok Masuk');
    }

    public function test_super_admin_can_record_incoming_stock_and_it_increases_product_stock(): void
    {
        $this->signIn();
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);

        $this->post('/stok', [
            'id_produk' => 1,
            'tanggal' => '2026-10-18',
            'jumlah' => 5,
            'keterangan' => 'Produksi pagi',
        ])->assertRedirect('/stok')->assertSessionHas('status');

        $this->assertDatabaseHas('ms_produk', [
            'id_produk' => 1,
            'stok' => 13,
        ]);

        $this->assertDatabaseHas('tr_stok', [
            'id_stok' => 1,
            'id_produk' => '1',
            'id_kategori' => 1,
            'tanggal' => '2026-10-18',
            'jumlah' => 5,
            'keterangan' => 'Produksi pagi',
            'ms_user_id_user' => '1',
            'ms_produk_id_produk' => '1',
        ]);
    }

    public function test_incoming_stock_can_initialize_product_with_null_stock(): void
    {
        $this->signIn();
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, null);

        $this->post('/stok', [
            'id_produk' => 1,
            'tanggal' => '2026-10-18',
            'jumlah' => 3,
        ])->assertRedirect('/stok');

        $this->assertDatabaseHas('ms_produk', ['id_produk' => 1, 'stok' => 3]);
        $this->assertDatabaseHas('tr_stok', ['id_stok' => 1, 'jumlah' => 3]);
    }

    public function test_stock_search_matches_product_and_note(): void
    {
        $this->signIn();
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);
        $this->createProduct(2, 1, 'Mochi Cokelat', 18000, 4);
        $this->createStockEntry(1, 1, 1, 'Mochi Admin', 'Batch stroberi');
        $this->createStockEntry(2, 2, 1, 'Mochi Admin', 'Batch cokelat');

        $response = $this->get('/stok?q=Stroberi');
        $response
            ->assertOk()
            ->assertSee('<td class="stock-product-cell">Mochi Stroberi</td>', false);
        $this->assertStringNotContainsString('<td class="stock-product-cell">Mochi Cokelat</td>', $response->getContent());

        $response = $this->get('/stok?q=Batch%20cokelat');
        $response
            ->assertOk()
            ->assertSee('<td class="stock-product-cell">Mochi Cokelat</td>', false);
        $this->assertStringNotContainsString('<td class="stock-product-cell">Mochi Stroberi</td>', $response->getContent());
    }

    public function test_cashier_can_view_stock_but_cannot_record_incoming_stock(): void
    {
        $this->signIn('Kaisr');
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);

        $this->get('/stok')->assertOk();

        $this->post('/stok', [
            'id_produk' => 1,
            'tanggal' => '2026-10-18',
            'jumlah' => 5,
        ])->assertForbidden();

        $this->assertDatabaseMissing('tr_stok', ['ms_produk_id_produk' => '1']);
    }

    public function test_stock_cannot_exceed_database_integer_limit(): void
    {
        $this->signIn();
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 2147483647);

        $this->from('/stok')->post('/stok', [
            'id_produk' => 1,
            'tanggal' => '2026-10-18',
            'jumlah' => 1,
        ])->assertRedirect('/stok')->assertSessionHasErrors('jumlah');

        $this->assertDatabaseHas('ms_produk', ['id_produk' => 1, 'stok' => 2147483647]);
        $this->assertDatabaseCount('tr_stok', 0);
    }

    private function signIn(string $role = 'SuperAdmin'): void
    {
        $user = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Mochi Admin',
            'email' => 'admin@example.com',
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

    private function createProduct(int $id, int $categoryId, string $name, int $price, ?int $stock): void
    {
        Schema::getConnection()->table('ms_produk')->insert([
            'id_produk' => $id,
            'id_kategori' => $categoryId,
            'nama_produk' => $name,
            'harga' => $price,
            'stok' => $stock,
        ]);
    }

    private function createStockEntry(int $id, int $productId, int $categoryId, string $userName, ?string $note = 'Produksi pagi'): void
    {
        Schema::getConnection()->table('tr_stok')->insert([
            'id_stok' => $id,
            'id_produk' => (string) $productId,
            'id_kategori' => $categoryId,
            'tanggal' => '2026-10-18',
            'jumlah' => 2,
            'keterangan' => $note,
            'ms_user_id_user' => '1',
            'ms_produk_id_produk' => (string) $productId,
        ]);

        Schema::getConnection()->table('ms_user')
            ->where('id_user', 1)
            ->update(['nama_lengkap' => $userName]);
    }
}
