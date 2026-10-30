<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftChangeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $employees = User::query()
            ->with('shift')
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nama_lengkap', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('no_telepon', 'like', '%'.$search.'%')
                        ->orWhere('role', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('nama_lengkap')
            ->paginate(12)
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'pendingShiftRequests' => ShiftChangeRequest::query()
                ->with(['user.shift', 'shift'])
                ->where('status', ShiftChangeRequest::STATUS_PENDING)
                ->latest('id_pengajuan')
                ->paginate(10, ['*'], 'shift_request_page')
                ->withQueryString(),
            'pendingShiftRequestCount' => ShiftChangeRequest::query()
                ->where('status', ShiftChangeRequest::STATUS_PENDING)
                ->count(),
            'search' => $filters['q'] ?? '',
            'employeeCount' => User::query()->count(),
            'activeEmployeeCount' => User::query()->where('status', 'Aktif')->count(),
            'cashierCount' => User::query()->where('role', 'Kasir')->count(),
        ]);
    }

    public function create(): View
    {
        return view('employees.form', [
            'employee' => new User,
            'shifts' => Shift::query()->orderBy('nama_shift')->get(),
            'editing' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateEmployee($request);
        $data['role'] = 'Kasir';

        DB::transaction(function () use ($data): void {
            $nextId = (int) DB::table('ms_user')->lockForUpdate()->max('id_user') + 1;
            if ($nextId > 2147483647) {
                throw new RuntimeException('ID karyawan telah mencapai batas maksimum database.');
            }

            User::query()->create([
                ...$data,
                'id_user' => $nextId,
            ]);
        });

        return redirect()->route('employees.index')->with('status', 'Akun kasir berhasil ditambahkan.');
    }

    public function edit(int $employee): View
    {
        $user = User::query()->findOrFail($employee);
        abort_unless($user->role === 'Kasir', 404);

        return view('employees.form', [
            'employee' => $user,
            'shifts' => Shift::query()->orderBy('nama_shift')->get(),
            'editing' => true,
        ]);
    }

    public function update(Request $request, int $employee): RedirectResponse
    {
        $user = User::query()->findOrFail($employee);
        abort_unless($user->role === 'Kasir', 404);

        $data = $this->validateEmployee($request, $user);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $data['role'] = 'Kasir';

        $user->fill($data)->save();

        return redirect()->route('employees.index')->with('status', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(int $employee): RedirectResponse
    {
        $user = User::query()->findOrFail($employee);
        abort_unless($user->role === 'Kasir', 404);

        foreach ([
            ['table' => 'tr_pesanan', 'column' => 'ms_user_id_user'],
            ['table' => 'tr_stok', 'column' => 'ms_user_id_user'],
        ] as $reference) {
            if (
                Schema::hasTable($reference['table'])
                && DB::table($reference['table'])->where($reference['column'], (string) $user->id_user)->exists()
            ) {
                return back()->with('error', 'Akun kasir memiliki riwayat transaksi atau stok sehingga tidak dapat dihapus. Nonaktifkan akun jika tidak lagi digunakan.');
            }
        }

        DB::transaction(function () use ($user): void {
            $user->shiftChangeRequests()
                ->where('status', ShiftChangeRequest::STATUS_PENDING)
                ->delete();
            $user->delete();
        });

        return redirect()->route('employees.index')->with('status', 'Akun karyawan berhasil dihapus.');
    }

    /**
     * @return array{nama_lengkap: string, email: string, no_telepon: string|null, alamat: string|null, id_shift: int|null, status: string, password: string|null}
     */
    private function validateEmployee(Request $request, ?User $employee = null): array
    {
        $passwordRules = $employee === null
            ? ['required', 'string', 'min:8', 'max:60']
            : ['nullable', 'string', 'min:8', 'max:60'];

        return $request->validate([
            'nama_lengkap' => [
                'required',
                'string',
                'max:30',
                Rule::unique('ms_user', 'nama_lengkap')->ignore($employee?->id_user, 'id_user'),
            ],
            'email' => [
                'required',
                'email',
                'max:45',
                Rule::unique('ms_user', 'email')->ignore($employee?->id_user, 'id_user'),
            ],
            'no_telepon' => ['nullable', 'string', 'max:15'],
            'alamat' => ['nullable', 'string', 'max:100'],
            'id_shift' => ['nullable', 'integer', Rule::exists('ms_shift', 'id_shift')],
            'status' => ['required', Rule::in(['Aktif', 'Tidak Aktif'])],
            'password' => $passwordRules,
        ], [
            'nama_lengkap.required' => 'Nama karyawan wajib diisi.',
            'nama_lengkap.max' => 'Nama karyawan maksimal 30 karakter.',
            'nama_lengkap.unique' => 'Nama pengguna tersebut sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'email.max' => 'Email maksimal 45 karakter.',
            'email.unique' => 'Email tersebut sudah digunakan.',
            'no_telepon.max' => 'Nomor telepon maksimal 15 karakter.',
            'alamat.max' => 'Alamat maksimal 100 karakter.',
            'id_shift.exists' => 'Shift yang dipilih tidak tersedia.',
            'status.in' => 'Status akun yang dipilih tidak valid.',
            'password.required' => 'Password wajib diisi untuk akun baru.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.max' => 'Password maksimal 60 karakter.',
        ]);
    }
}
