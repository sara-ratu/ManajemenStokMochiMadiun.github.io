<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfileTest extends TestCase
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

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/profil')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_profile_and_shift_details(): void
    {
        Schema::getConnection()->table('ms_shift')->insert([
            'id_shift' => 1,
            'nama_shift' => 'Pagi',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '16:00:00',
        ]);

        $user = User::query()->create([
            'id_user' => 1,
            'id_shift' => 1,
            'nama_lengkap' => 'Mochi Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'no_telepon' => '08123456789',
            'alamat' => 'Madiun',
            'status' => 'Aktif',
            'role' => 'SuperAdmin',
        ]);

        $this->actingAs($user)
            ->get('/profil')
            ->assertOk()
            ->assertSee('Profil Saya')
            ->assertSee('Mochi Admin')
            ->assertSee('Super Admin')
            ->assertSee('admin@example.com')
            ->assertSee('08123456789')
            ->assertSee('Madiun')
            ->assertSee('Pagi')
            ->assertSee('08:00–16:00')
            ->assertDontSee('Ringkasan shift Anda')
            ->assertDontSee('secret-password');
    }

    public function test_cashier_can_view_cashier_profile_and_shift_summary(): void
    {
        Schema::getConnection()->table('ms_shift')->insert([
            'id_shift' => 2,
            'nama_shift' => 'Sore',
            'jam_mulai' => '16:00:00',
            'jam_selesai' => '22:00:00',
        ]);

        $user = User::query()->create([
            'id_user' => 2,
            'id_shift' => 2,
            'nama_lengkap' => 'Kasir Mochi',
            'email' => 'kasir@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'Kaisr',
        ]);

        $this->actingAs($user)
            ->get('/profil')
            ->assertOk()
            ->assertSee('Profil Kasir')
            ->assertSee('AKUN KASIR')
            ->assertSee('Kelola informasi akun dan lihat jadwal kerja Anda.')
            ->assertSee('Ringkasan shift Anda')
            ->assertSee('Sore')
            ->assertSee('16:00–22:00')
            ->assertSee('Buka halaman kasir')
            ->assertSee('Kembali ke kasir')
            ->assertDontSee('Kembali ke produk');
    }

    public function test_user_can_update_personal_profile_without_changing_role_or_shift(): void
    {
        $user = User::query()->create([
            'id_user' => 1,
            'id_shift' => null,
            'nama_lengkap' => 'Kasir Lama',
            'email' => 'kasir@example.com',
            'password' => Hash::make('secret-password'),
            'no_telepon' => '08111111111',
            'alamat' => 'Alamat lama',
            'status' => 'Aktif',
            'role' => 'Kaisr',
        ]);

        $this->actingAs($user)->put('/profil', [
            'nama_lengkap' => 'Kasir Baru',
            'email' => 'kasirbaru@example.com',
            'no_telepon' => '08222222222',
            'alamat' => 'Alamat baru',
            'role' => 'SuperAdmin',
            'status' => 'TidakAktif',
            'id_shift' => 10,
        ])->assertRedirect('/profil')->assertSessionHas('status');

        $this->assertDatabaseHas('ms_user', [
            'id_user' => 1,
            'nama_lengkap' => 'Kasir Baru',
            'email' => 'kasirbaru@example.com',
            'no_telepon' => '08222222222',
            'alamat' => 'Alamat baru',
            'role' => 'Kaisr',
            'status' => 'Aktif',
            'id_shift' => null,
        ]);
    }

    public function test_user_can_change_password_after_confirming_current_password(): void
    {
        $user = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Mochi Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'SuperAdmin',
        ]);

        $this->actingAs($user)->put('/profil', [
            'nama_lengkap' => $user->nama_lengkap,
            'email' => $user->email,
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
            'current_password' => 'secret-password',
        ])->assertRedirect('/profil');

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
        $this->assertFalse(Hash::check('secret-password', $user->fresh()->password));
    }

    public function test_user_cannot_change_password_with_an_incorrect_current_password(): void
    {
        $user = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Mochi Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'SuperAdmin',
        ]);

        $this->actingAs($user)->from('/profil')->put('/profil', [
            'nama_lengkap' => $user->nama_lengkap,
            'email' => $user->email,
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
            'current_password' => 'wrong-password',
        ])->assertRedirect('/profil')->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('secret-password', $user->fresh()->password));
    }
}
