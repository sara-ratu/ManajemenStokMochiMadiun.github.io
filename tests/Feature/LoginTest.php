<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_login_page_is_available_at_home_and_login_routes(): void
    {
        $this->get('/')->assertOk()->assertSee('MOCHI MADIUN');
        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    public function test_user_can_login_with_name_and_password(): void
    {
        $user = $this->createActiveUser();

        $this->post('/login', [
            'username' => $user->nama_lengkap,
            'password' => 'secret-password',
        ])->assertRedirect('/produk');

        $this->assertAuthenticatedAs($user);
        $this->get('/')->assertSee('Berhasil masuk!');
    }

    public function test_user_can_login_with_email(): void
    {
        $user = $this->createActiveUser();

        $this->post('/login', [
            'username' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect('/produk');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_return_to_login_with_an_error(): void
    {
        $this->createActiveUser();

        $this->from('/login')->post('/login', [
            'username' => 'Mochi Admin',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_login(): void
    {
        $user = $this->createActiveUser(['status' => 'TidakAktif']);

        $this->from('/login')->post('/login', [
            'username' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->createActiveUser();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    private function createActiveUser(array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'id_user' => 1,
            'nama_lengkap' => 'Mochi Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'SuperAdmin',
        ], $attributes));
    }
}
