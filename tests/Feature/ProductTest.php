<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
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
            $table->increments('id_kategori');
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
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/produk')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_products_and_category(): void
    {
        $this->signIn();
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);

        $this->get('/produk')
            ->assertOk()
            ->assertSee('MOCHI MADIUN')
            ->assertSee('<div class="topbar-page-label">Produk</div>', false)
            ->assertSee('KATALOG TOKO')
            ->assertDontSee('<h1>Produk</h1>', false)
            ->assertSee('Total Kategori')
            ->assertSee('data-live-clock', false)
            ->assertSeeInOrder(['ID Produk', 'Nama Produk', 'ID Kategori', 'Nama Kategori', 'Harga', 'Stok', 'Gambar', 'Aksi'])
            ->assertSee('<td class="product-col-id">1</td>', false)
            ->assertSee('<td class="product-col-category-id">1</td>', false)
            ->assertSee('Mochi Stroberi')
            ->assertSee('Mochi Klasik')
            ->assertSee('Rp15.000')
            ->assertSee('8 pcs')
            ->assertSee('Ubah Mochi Stroberi')
            ->assertSee('Hapus Mochi Stroberi');
    }

    public function test_super_admin_sees_centered_add_product_dialog_form(): void
    {
        $this->signIn();
        $this->createCategory(1, 'Mochi Klasik');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);

        $this->get('/produk')
            ->assertOk()
            ->assertSee('data-open-product-dialog', false)
            ->assertSee('data-product-dialog', false)
            ->assertSee('Tambah Kategori')
            ->assertSee('Simpan Kategori')
            ->assertSee('Gambar Produk')
            ->assertSee('Kategori belum ada?')
            ->assertSee('Mis. Mochi original')
            ->assertSee('Simpan Produk')
            ->assertSee('Batal')
            ->assertSee('data-confirm-delete-action', false)
            ->assertSee('Ya, hapus')
            ->assertSee('edit-id-produk-', false)
            ->assertSee('name="id_kategori"', false)
            ->assertDontSee('Masukkan ID kategori')
            ->assertSee('edit-stok-', false)
            ->assertDontSee('name="stok"', false)
            ->assertDontSee('dialog-stok', false);
    }

    public function test_products_can_be_searched_and_filtered_by_category(): void
    {
        $this->signIn();
        $this->createCategory(1, 'Mochi Klasik');
        $this->createCategory(2, 'Mochi Premium');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);
        $this->createProduct(2, 2, 'Mochi Cokelat', 18000, 0);

        $this->get('/produk?q=Stroberi&kategori=1')
            ->assertOk()
            ->assertSee('Mochi Stroberi')
            ->assertDontSee('Mochi Cokelat');
    }

    public function test_products_can_be_searched_by_category_name(): void
    {
        $this->signIn();
        $this->createCategory(1, 'Mochi Klasik');
        $this->createCategory(2, 'Mochi Premium');
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);
        $this->createProduct(2, 2, 'Mochi Cokelat', 18000, 0);

        $this->get('/produk?q=Premium')
            ->assertOk()
            ->assertSee('Mochi Cokelat')
            ->assertDontSee('Mochi Stroberi');
    }

    public function test_super_admin_can_create_update_and_delete_a_product(): void
    {
        $this->signIn();
        $this->createCategory(1, 'Mochi Klasik');
        $this->createCategory(2, 'Mochi Premium');

        $this->post('/produk', [
            'nama_produk' => 'Mochi Stroberi',
            'id_kategori' => 1,
            'harga' => 15000,
            'stok' => 40,
        ])->assertRedirect('/produk')->assertSessionHas('status');

        $this->assertDatabaseHas('ms_produk', [
            'id_produk' => 1,
            'nama_produk' => 'Mochi Stroberi',
            'harga' => 15000,
            'stok' => null,
        ]);

        Schema::getConnection()->table('ms_produk')
            ->where('id_produk', 1)
            ->update(['stok' => 8]);

        $this->get('/produk/1/edit')
            ->assertOk()
            ->assertSee('ID Produk')
            ->assertSee('Kategori')
            ->assertSee('Stok')
            ->assertSee('id="stok"', false)
            ->assertSee('id="id_kategori" name="id_kategori"', false)
            ->assertDontSee('Masukkan ID kategori')
            ->assertDontSee('name="stok"', false);

        $this->put('/produk/1', [
            'nama_produk' => 'Mochi Cokelat',
            'id_kategori' => 2,
            'harga' => 18000,
            'stok' => 999,
        ])->assertRedirect('/produk')->assertSessionHas('status');

        $this->assertDatabaseHas('ms_produk', [
            'id_produk' => 1,
            'nama_produk' => 'Mochi Cokelat',
            'id_kategori' => 2,
            'harga' => 18000,
            'stok' => 8,
        ]);

        $this->delete('/produk/1')->assertRedirect('/produk')->assertSessionHas('status');
        $this->assertDatabaseMissing('ms_produk', ['id_produk' => 1]);
    }

    public function test_super_admin_can_create_a_product_with_an_image_url(): void
    {
        $this->signIn();

        $this->from('/produk')->post('/produk', [
            'gambar_url' => 'https://example.com/mochi.jpg',
            'nama_produk' => 'Mochi Original',
            'id_kategori' => '',
            'harga' => 8000,
        ])->assertRedirect('/produk')->assertSessionHas('status');

        $this->assertDatabaseHas('ms_produk', [
            'nama_produk' => 'Mochi Original',
            'gambar' => 'https://example.com/mochi.jpg',
            'harga' => 8000,
            'stok' => null,
        ]);
    }

    public function test_super_admin_can_create_category_and_use_it_for_product(): void
    {
        $this->signIn();

        $this->post('/kategori', [
            'form_type' => 'category',
            'nama_kategori' => 'Mochi Premium',
        ])->assertRedirect('/produk')->assertSessionHas('status');

        $this->assertDatabaseHas('ms_kategori', [
            'id_kategori' => 1,
            'nama_kategori' => 'Mochi Premium',
        ]);

        $this->get('/produk')
            ->assertOk()
            ->assertSee('Mochi Premium')
            ->assertSee('Tambah Kategori');

        $this->post('/produk', [
            'form_type' => 'product',
            'nama_produk' => 'Mochi Matcha',
            'id_kategori' => 1,
            'harga' => 18000,
        ])->assertRedirect('/produk')->assertSessionHas('status');

        $this->assertDatabaseHas('ms_produk', [
            'nama_produk' => 'Mochi Matcha',
            'id_kategori' => 1,
        ]);
    }

    public function test_duplicate_category_name_is_rejected_and_add_category_dialog_reopens(): void
    {
        $this->signIn();
        $this->createCategory(1, 'Mochi Premium');

        $this->from('/produk')->post('/kategori', [
            'form_type' => 'category',
            'nama_kategori' => 'Mochi Premium',
        ])->assertRedirect('/produk')->assertSessionHasErrors('nama_kategori');

        $this->get('/produk')
            ->assertOk()
            ->assertSee('data-open-on-load', false)
            ->assertSee('Nama kategori tersebut sudah digunakan.');

        $this->assertDatabaseCount('ms_kategori', 1);
    }

    public function test_invalid_product_image_url_returns_to_catalog_with_dialog_errors(): void
    {
        $this->signIn();

        $this->from('/produk')->post('/produk', [
            'gambar_url' => 'javascript:alert(1)',
            'nama_produk' => 'Mochi Original',
            'harga' => 8000,
        ])->assertRedirect('/produk')->assertSessionHasErrors('gambar_url');

        $this->get('/produk')
            ->assertOk()
            ->assertSee('data-open-on-load', false)
            ->assertSee('URL gambar yang valid');
    }

    public function test_super_admin_can_upload_a_product_image(): void
    {
        Storage::fake('public');
        $this->signIn();

        $this->post('/produk', [
            'nama_produk' => 'Mochi Stroberi',
            'harga' => 15000,
            'gambar' => UploadedFile::fake()->image('mochi.jpg'),
        ])->assertRedirect('/produk');

        $product = Product::query()->firstOrFail();
        $this->assertNotNull($product->gambar);
        Storage::disk('public')->assertExists($product->gambar);
    }

    public function test_cashier_cannot_manage_products(): void
    {
        $this->signIn('Kasir');

        $this->get('/produk')->assertForbidden();
        $this->get('/produk/tambah')->assertForbidden();
        $this->post('/kategori', ['nama_kategori' => 'Kategori Ilegal'])->assertForbidden();
        $this->post('/produk', [
            'nama_produk' => 'Produk Ilegal',
            'harga' => 1000,
        ])->assertForbidden();
    }

    public function test_product_with_stock_history_cannot_be_deleted(): void
    {
        $this->signIn();
        $this->createProduct(1, 1, 'Mochi Stroberi', 15000, 8);
        Schema::create('tr_stok', function ($table): void {
            $table->integer('id_stok')->primary();
            $table->string('ms_produk_id_produk', 10);
        });
        Schema::getConnection()->table('tr_stok')->insert([
            'id_stok' => 1,
            'ms_produk_id_produk' => '1',
        ]);

        $this->delete('/produk/1')->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('ms_produk', ['id_produk' => 1]);
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
