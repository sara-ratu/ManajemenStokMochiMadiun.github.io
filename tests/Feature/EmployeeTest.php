<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('ms_user');
        Schema::dropIfExists('ms_shift');
        Schema::create('ms_shift', function ($table): void {
            $table->integer('id_shift')->primary();
            $table->string('nama_shift', 20)->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
        });

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
    }

    public function test_super_admin_can_view_employee_data_and_shift(): void
    {
        Schema::getConnection()->table('ms_shift')->insert([
            'id_shift' => 1,
            'nama_shift' => 'Pagi',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '16:00:00',
        ]);

        $admin = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Admin Mochi',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'SuperAdmin',
        ]);
        User::query()->create([
            'id_user' => 2,
            'id_shift' => 1,
            'nama_lengkap' => 'Kasir Mochi',
            'email' => 'kasir@example.com',
            'password' => Hash::make('secret-password'),
            'no_telepon' => '08123456789',
            'status' => 'Aktif',
            'role' => 'Kasir',
        ]);

        $this->actingAs($admin)
            ->get('/karyawan')
            ->assertOk()
            ->assertSee('Data Karyawan')
            ->assertSee('Kasir Mochi')
            ->assertSee('kasir@example.com')
            ->assertSee('08123456789')
            ->assertSee('Pagi')
            ->assertSee('08:00–16:00')
            ->assertSee('Total Karyawan')
            ->assertSee('Laporan Penjualan')
            ->assertDontSee('secret-password');
    }

    public function test_employee_search_filters_the_list(): void
    {
        $admin = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Admin Mochi',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'SuperAdmin',
        ]);
        User::query()->create([
            'id_user' => 2,
            'nama_lengkap' => 'Kasir Pagi',
            'email' => 'kasir@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'Kasir',
        ]);
        User::query()->create([
            'id_user' => 3,
            'nama_lengkap' => 'Kasir Sore',
            'email' => 'sore@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'Kasir',
        ]);

        $this->actingAs($admin)
            ->get('/karyawan?q=Pagi')
            ->assertOk()
            ->assertSee('Kasir Pagi')
            ->assertDontSee('Kasir Sore');
    }

    public function test_cashier_cannot_view_employee_data(): void
    {
        $cashier = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Kasir Mochi',
            'email' => 'kasir@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'Kasir',
        ]);

        $this->actingAs($cashier)->get('/karyawan')->assertForbidden();
    }
}
