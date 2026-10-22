<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('ms_user');
        Schema::dropIfExists('ms_produk');
        Schema::dropIfExists('tr_pesanan');

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

        Schema::create('ms_produk', function ($table): void {
            $table->integer('id_produk')->primary();
            $table->string('nama_produk', 30);
            $table->integer('id_kategori')->nullable();
            $table->integer('harga');
            $table->integer('stok')->default(0);
            $table->string('gambar', 100)->nullable();
        });

        Schema::create('tr_pesanan', function ($table): void {
            $table->integer('id_pesanan')->primary();
            $table->dateTime('tanggal');
            $table->unsignedInteger('total');
            $table->string('status', 12);
            $table->string('ms_user_id_user', 20)->nullable();
        });
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_super_admin_sees_store_summary_and_management_links(): void
    {
        $admin = $this->createUser(1, 'SuperAdmin', 'Admin Mochi');
        $this->createUser(2, 'Kasir', 'Kasir Mochi');
        $this->createProduct(1, 'Mochi Kacang', 18);
        $this->createProduct(2, 'Mochi Coklat', 3);
        $this->createOrder(1, 'Selesai', 50000);
        $this->createOrder(2, 'Pending', 20000);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('DASHBOARD SUPER ADMIN')
            ->assertSee('Total Karyawan')
            ->assertSee('Total Produk')
            ->assertSee('Total Stok')
            ->assertSee('Omzet Hari Ini')
            ->assertSee('Data Karyawan')
            ->assertSee('Laporan Penjualan')
            ->assertDontSee('Order Pelanggan');
    }

    public function test_cashier_sees_pending_orders_and_cashier_links_only(): void
    {
        $cashier = $this->createUser(1, 'Kasir', 'Kasir Mochi');
        $this->createProduct(1, 'Mochi Kacang', 18);
        $this->createOrder(1, 'Selesai', 50000);
        $this->createOrder(2, 'Pending', 20000);

        $this->actingAs($cashier)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('DASHBOARD KASIR')
            ->assertSee('Transaksi Hari Ini')
            ->assertSee('Order Menunggu')
            ->assertSee('Order Pelanggan')
            ->assertSee('Kelola Stok')
            ->assertDontSee('Data Karyawan')
            ->assertDontSee('Produk</strong>');
    }

    private function createUser(int $id, string $role, string $name): User
    {
        return User::query()->create([
            'id_user' => $id,
            'nama_lengkap' => $name,
            'email' => strtolower(str_replace(' ', '', $name)).'@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => $role,
        ]);
    }

    private function createProduct(int $id, string $name, int $stock): void
    {
        Schema::getConnection()->table('ms_produk')->insert([
            'id_produk' => $id,
            'nama_produk' => $name,
            'harga' => 10000,
            'stok' => $stock,
        ]);
    }

    private function createOrder(int $id, string $status, int $total): void
    {
        Schema::getConnection()->table('tr_pesanan')->insert([
            'id_pesanan' => $id,
            'tanggal' => now('Asia/Jakarta')->toDateTimeString(),
            'total' => $total,
            'status' => $status,
        ]);
    }
}
