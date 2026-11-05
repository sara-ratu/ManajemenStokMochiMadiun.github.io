<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ShiftChangeRequest;
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
            ->assertSee('data-open-product-dialog="add-employee-dialog"', false)
            ->assertSee('id="add-employee-dialog"', false)
            ->assertSee('name="form_type" value="employee"', false)
            ->assertSee('Kasir Mochi')
            ->assertSee('kasir@example.com')
            ->assertSee('08123456789')
            ->assertSee('Pagi')
            ->assertSee('08:00–16:00')
            ->assertSee('Total Karyawan')
            ->assertSeeInOrder(['Beranda', 'Laporan Penjualan'])
            ->assertDontSee('secret-password');
    }

    public function test_invalid_employee_create_reopens_the_add_modal_with_errors(): void
    {
        $admin = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Admin Mochi',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'SuperAdmin',
        ]);

        $this->actingAs($admin)
            ->from('/karyawan')
            ->post('/karyawan', [
                'form_type' => 'employee',
                'nama_lengkap' => '',
                'email' => 'bukan-email',
            ])
            ->assertRedirect('/karyawan')
            ->assertSessionHasErrors(['nama_lengkap', 'email', 'password']);

        $this->get('/karyawan')
            ->assertOk()
            ->assertSee('id="add-employee-dialog"', false)
            ->assertSee('data-open-on-load', false)
            ->assertSee('value="bukan-email"', false)
            ->assertSee('Periksa kembali data karyawan yang Anda masukkan.');
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

    public function test_super_admin_can_create_a_cashier_account(): void
    {
        $admin = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Admin Mochi',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'SuperAdmin',
        ]);
        Schema::getConnection()->table('ms_shift')->insert([
            'id_shift' => 1,
            'nama_shift' => 'Pagi',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '16:00:00',
        ]);
        Schema::getConnection()->table('ms_shift')->insert([
            [
                'id_shift' => 2,
                'nama_shift' => 'Siang',
                'jam_mulai' => null,
                'jam_selesai' => null,
            ],
            [
                'id_shift' => 3,
                'nama_shift' => 'Oncalling',
                'jam_mulai' => null,
                'jam_selesai' => null,
            ],
        ]);

        $this->actingAs($admin)
            ->get('/karyawan/tambah')
            ->assertOk()
            ->assertSee('Tambah Karyawan')
            ->assertSee('Kasir')
            ->assertSee('Nama pengguna')
            ->assertSee('Pagi')
            ->assertSee('Siang')
            ->assertSee('Oncalling');

        $this->actingAs($admin)
            ->post('/karyawan', [
                'nama_lengkap' => 'Kasir Baru',
                'email' => 'kasir-baru@example.com',
                'no_telepon' => '08123456789',
                'alamat' => 'Jalan Mochi',
                'id_shift' => 3,
                'status' => 'Aktif',
                'password' => 'password-baru',
            ])
            ->assertRedirect('/karyawan');

        $employee = User::query()->where('email', 'kasir-baru@example.com')->firstOrFail();
        $this->assertSame(2, $employee->id_user);
        $this->assertSame('Kasir', $employee->role);
        $this->assertSame(3, $employee->id_shift);
        $this->assertTrue(Hash::check('password-baru', $employee->password));
    }

    public function test_super_admin_can_update_cashier_and_keep_password_when_blank(): void
    {
        $admin = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Admin Mochi',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'SuperAdmin',
        ]);
        $cashier = User::query()->create([
            'id_user' => 2,
            'nama_lengkap' => 'Kasir Pagi',
            'email' => 'kasir@example.com',
            'password' => 'password-lama',
            'status' => 'Aktif',
            'role' => 'Kasir',
        ]);
        $oldPassword = $cashier->password;

        $this->actingAs($admin)
            ->get('/karyawan')
            ->assertOk()
            ->assertSee('data-open-product-dialog="edit-employee-2"', false)
            ->assertSee('id="edit-employee-2"', false)
            ->assertSee('data-confirm-employee-update', false)
            ->assertSee('data-employee-save-confirm-dialog', false)
            ->assertSee('Simpan Perubahan?')
            ->assertSee('Ya, simpan')
            ->assertSee('Ubah Karyawan')
            ->assertSee('Kasir Pagi');

        $this->actingAs($admin)
            ->get('/karyawan/2/edit')
            ->assertOk()
            ->assertSee('Kasir Pagi')
            ->assertDontSee('Ubah Data Karyawan');

        $this->actingAs($admin)
            ->put('/karyawan/2', [
                'nama_lengkap' => 'Kasir Diperbarui',
                'email' => 'kasir-baru@example.com',
                'no_telepon' => '08111111111',
                'alamat' => '',
                'id_shift' => '',
                'status' => 'Tidak Aktif',
                'password' => '',
            ])
            ->assertRedirect('/karyawan');

        $cashier->refresh();
        $this->assertSame('Kasir Diperbarui', $cashier->nama_lengkap);
        $this->assertSame('Tidak Aktif', $cashier->status);
        $this->assertSame($oldPassword, $cashier->password);
        $this->assertSame('Kasir', $cashier->role);
    }

    public function test_super_admin_can_delete_cashier_without_history(): void
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
            'nama_lengkap' => 'Kasir Lama',
            'email' => 'kasir@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'Kasir',
        ]);

        $this->actingAs($admin)
            ->delete('/karyawan/2')
            ->assertRedirect('/karyawan');

        $this->assertDatabaseMissing('ms_user', ['id_user' => 2]);
    }

    public function test_cashier_cannot_manage_employee_accounts(): void
    {
        $cashier = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Kasir Mochi',
            'email' => 'kasir@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'Kasir',
        ]);

        $this->actingAs($cashier)
            ->post('/karyawan', [])
            ->assertForbidden();
    }

    public function test_cashier_with_transaction_history_cannot_be_deleted(): void
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
            'nama_lengkap' => 'Kasir Riwayat',
            'email' => 'kasir@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'Kasir',
        ]);
        Schema::getConnection()->table('tr_pesanan')->insert([
            'id_pesanan' => 1,
            'tanggal' => now()->toDateTimeString(),
            'total' => 1000,
            'status' => 'Selesai',
            'ms_user_id_user' => '2',
        ]);

        $this->actingAs($admin)
            ->from('/karyawan')
            ->delete('/karyawan/2')
            ->assertRedirect('/karyawan')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('ms_user', ['id_user' => 2]);
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

    public function test_cashier_can_request_shift_change_without_changing_current_shift(): void
    {
        Schema::getConnection()->table('ms_shift')->insert([
            ['id_shift' => 1, 'nama_shift' => 'Pagi', 'jam_mulai' => '08:00:00', 'jam_selesai' => '16:00:00'],
            ['id_shift' => 2, 'nama_shift' => 'Siang', 'jam_mulai' => '12:00:00', 'jam_selesai' => '20:00:00'],
        ]);
        $cashier = User::query()->create([
            'id_user' => 1,
            'id_shift' => 1,
            'nama_lengkap' => 'Kasir Mochi',
            'email' => 'kasir@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'Kasir',
        ]);

        $this->actingAs($cashier)
            ->post('/pengajuan-shift', ['id_shift' => 2])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('tr_pengajuan_shift', [
            'id_user' => 1,
            'id_shift' => 2,
            'status' => ShiftChangeRequest::STATUS_PENDING,
        ]);
        $this->assertSame(1, $cashier->fresh()->id_shift);

        $this->get('/profil')
            ->assertOk()
            ->assertSee('sedang menunggu persetujuan administrator');
    }

    public function test_cashier_cannot_request_current_shift_or_an_unavailable_shift(): void
    {
        Schema::getConnection()->table('ms_shift')->insert([
            ['id_shift' => 1, 'nama_shift' => 'Pagi', 'jam_mulai' => '08:00:00', 'jam_selesai' => '16:00:00'],
            ['id_shift' => 2, 'nama_shift' => 'Siang', 'jam_mulai' => '12:00:00', 'jam_selesai' => '20:00:00'],
            ['id_shift' => 3, 'nama_shift' => 'Oncalling', 'jam_mulai' => null, 'jam_selesai' => null],
        ]);
        $cashier = User::query()->create([
            'id_user' => 1,
            'id_shift' => 1,
            'nama_lengkap' => 'Kasir Mochi',
            'email' => 'kasir@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'Kasir',
        ]);

        $this->actingAs($cashier)
            ->from('/profil')
            ->post('/pengajuan-shift', ['id_shift' => 1])
            ->assertRedirect('/profil')
            ->assertSessionHas('error');

        $this->actingAs($cashier)
            ->from('/profil')
            ->post('/pengajuan-shift', ['id_shift' => 3])
            ->assertRedirect('/profil')
            ->assertSessionHasErrors('id_shift');

        $this->assertDatabaseCount('tr_pengajuan_shift', 0);
    }

    public function test_super_admin_can_approve_shift_request_and_notify_dashboard(): void
    {
        Schema::getConnection()->table('ms_shift')->insert([
            ['id_shift' => 1, 'nama_shift' => 'Pagi', 'jam_mulai' => '08:00:00', 'jam_selesai' => '16:00:00'],
            ['id_shift' => 2, 'nama_shift' => 'Siang', 'jam_mulai' => '12:00:00', 'jam_selesai' => '20:00:00'],
        ]);
        $admin = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Admin Mochi',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'SuperAdmin',
        ]);
        $cashier = User::query()->create([
            'id_user' => 2,
            'id_shift' => 1,
            'nama_lengkap' => 'Kasir Mochi',
            'email' => 'kasir@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'Kasir',
        ]);
        $shiftRequest = ShiftChangeRequest::query()->create([
            'id_user' => $cashier->id_user,
            'id_shift' => 2,
        ]);

        $this->actingAs($admin)
            ->get('/karyawan')
            ->assertOk()
            ->assertSee('Pengajuan Perubahan Shift')
            ->assertSee('Kasir Mochi')
            ->assertSee('Pagi')
            ->assertSee('Siang')
            ->assertSee('Setujui')
            ->assertSee('Tolak');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('1 pengajuan shift menunggu persetujuan')
            ->assertSee('data-pending-shift-count', false);

        $this->get('/pengajuan-shift/notifikasi')
            ->assertOk()
            ->assertExactJson(['pending_count' => 1]);

        $this->post("/pengajuan-shift/{$shiftRequest->id_pengajuan}/setujui")
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(2, $cashier->fresh()->id_shift);
        $this->assertDatabaseHas('tr_pengajuan_shift', [
            'id_pengajuan' => $shiftRequest->id_pengajuan,
            'status' => ShiftChangeRequest::STATUS_APPROVED,
            'id_admin' => $admin->id_user,
        ]);
        $this->get('/pengajuan-shift/notifikasi')
            ->assertExactJson(['pending_count' => 0]);
    }

    public function test_super_admin_can_reject_shift_request_without_changing_employee_shift(): void
    {
        Schema::getConnection()->table('ms_shift')->insert([
            ['id_shift' => 1, 'nama_shift' => 'Pagi', 'jam_mulai' => '08:00:00', 'jam_selesai' => '16:00:00'],
            ['id_shift' => 2, 'nama_shift' => 'Siang', 'jam_mulai' => '12:00:00', 'jam_selesai' => '20:00:00'],
        ]);
        $admin = User::query()->create([
            'id_user' => 1,
            'nama_lengkap' => 'Admin Mochi',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'SuperAdmin',
        ]);
        $cashier = User::query()->create([
            'id_user' => 2,
            'id_shift' => 1,
            'nama_lengkap' => 'Kasir Mochi',
            'email' => 'kasir@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Aktif',
            'role' => 'Kasir',
        ]);
        $shiftRequest = ShiftChangeRequest::query()->create([
            'id_user' => $cashier->id_user,
            'id_shift' => 2,
        ]);

        $this->actingAs($admin)
            ->post("/pengajuan-shift/{$shiftRequest->id_pengajuan}/tolak")
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(1, $cashier->fresh()->id_shift);
        $this->assertDatabaseHas('tr_pengajuan_shift', [
            'id_pengajuan' => $shiftRequest->id_pengajuan,
            'status' => ShiftChangeRequest::STATUS_REJECTED,
            'id_admin' => $admin->id_user,
        ]);
    }
}
