<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
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
            $table->string('foto_profil')->nullable();
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
            ->assertSee('Foto profil')
            ->assertSee('Unggah foto')
            ->assertSee('Administrator')
            ->assertSee('admin@example.com')
            ->assertSee('08123456789')
            ->assertDontSee('Konfirmasi perubahan profil')
            ->assertSee('Simpan profil')
            ->assertSee('Ganti Password')
            ->assertSee('Simpan password')
            ->assertSee('Madiun')
            ->assertSee('Pagi')
            ->assertSee('08:00–16:00')
            ->assertDontSee('Ringkasan shift Anda')
            ->assertDontSee('Kembali ke beranda')
            ->assertDontSee('secret-password');
    }

    public function test_cashier_profile_uses_shared_layout_and_shows_shift_details(): void
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
            'role' => 'Kasir',
        ]);

        $response = $this->actingAs($user)
            ->get('/profil')
            ->assertOk()
            ->assertSee('Profil Saya')
            ->assertSee('AKUN SAYA')
            ->assertSee('Perbarui informasi pribadi atau ubah password akun Anda.')
            ->assertSee('Sore')
            ->assertSee('16:00–22:00')
            ->assertDontSee('Kembali ke beranda')
            ->assertDontSee('Ringkasan shift Anda')
            ->assertDontSee('Buka halaman kasir')
            ->assertDontSee('Profil Kasir')
            ->assertDontSee('Kembali ke produk');

        $this->assertSame(2, substr_count($response->getContent(), 'Profil Saya'));
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
            'role' => 'Kasir',
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
            'role' => 'Kasir',
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

        $this->actingAs($user)->put('/profil/password', [
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
            'current_password' => 'secret-password',
        ])->assertRedirect('/profil')->assertSessionHas('status', 'Password berhasil diperbarui.');

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

        $this->actingAs($user)->from('/profil')->put('/profil/password', [
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
            'current_password' => 'wrong-password',
        ])->assertRedirect('/profil')->assertSessionHasErrorsIn('password', 'current_password');

        $this->assertTrue(Hash::check('secret-password', $user->fresh()->password));
    }

    public function test_profile_edit_does_not_require_current_password(): void
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
            'nama_lengkap' => 'Admin Baru',
            'email' => 'adminbaru@example.com',
        ])->assertRedirect('/profil')->assertSessionHas('status', 'Profil berhasil diperbarui.');

        $this->assertDatabaseHas('ms_user', [
            'id_user' => 1,
            'nama_lengkap' => 'Admin Baru',
            'email' => 'adminbaru@example.com',
        ]);
        $this->assertTrue(Hash::check('secret-password', $user->fresh()->password));
    }

    public function test_user_can_upload_and_remove_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Mochi Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'SuperAdmin',
        ]);

        $this->actingAs($user)->post('/profil/foto', [
            'foto_profil' => UploadedFile::fake()->image('profil.jpg'),
        ])->assertRedirect('/profil')
            ->assertSessionHas('status', 'Foto profil berhasil diperbarui.');

        $photoPath = $user->fresh()->foto_profil;
        $this->assertNotNull($photoPath);
        Storage::disk('public')->assertExists($photoPath);
        $this->get('/profil')->assertSee(Storage::disk('public')->url($photoPath));

        $this->post('/profil/foto', ['hapus_foto_profil' => '1'])
            ->assertRedirect('/profil');

        $this->assertNull($user->fresh()->foto_profil);
        Storage::disk('public')->assertMissing($photoPath);
    }

    public function test_profile_photo_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $user = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Mochi Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'SuperAdmin',
        ]);

        $this->actingAs($user)->from('/profil')->post('/profil/foto', [
            'foto_profil' => UploadedFile::fake()->create('file.txt', 10, 'text/plain'),
        ])->assertRedirect('/profil')
            ->assertSessionHasErrorsIn('photo', 'foto_profil');

        $this->assertNull($user->fresh()->foto_profil);
    }
}
