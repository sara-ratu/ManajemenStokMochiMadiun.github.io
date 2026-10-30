<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftChangeRequest;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShiftChangeRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'Kasir', 403);

        $data = $request->validate([
            'id_shift' => [
                'required',
                'integer',
                Rule::exists('ms_shift', 'id_shift')
                    ->where(fn (Builder $query) => $query->whereIn('nama_shift', ['Pagi', 'Siang'])),
            ],
        ], [
            'id_shift.required' => 'Pilih shift yang ingin diajukan.',
            'id_shift.exists' => 'Pilih shift Pagi atau Siang yang tersedia.',
        ]);

        $result = DB::transaction(function () use ($request, $data): string {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());

            if ((int) $user->id_shift === (int) $data['id_shift']) {
                return 'same';
            }

            if ($user->shiftChangeRequests()->where('status', ShiftChangeRequest::STATUS_PENDING)->exists()) {
                return 'pending';
            }

            $user->shiftChangeRequests()->create([
                'id_shift' => $data['id_shift'],
                'status' => ShiftChangeRequest::STATUS_PENDING,
            ]);

            return 'created';
        });

        if ($result === 'same') {
            return back()->with('error', 'Shift yang dipilih sama dengan shift Anda saat ini.');
        }

        if ($result === 'pending') {
            return back()->with('error', 'Anda masih memiliki pengajuan shift yang menunggu persetujuan.');
        }

        return back()->with('status', 'Pengajuan perubahan shift berhasil dikirim ke administrator.');
    }

    public function approve(int $shiftRequest): RedirectResponse
    {
        return $this->review($shiftRequest, true, request()->user());
    }

    public function reject(int $shiftRequest): RedirectResponse
    {
        return $this->review($shiftRequest, false, request()->user());
    }

    public function notifications(): JsonResponse
    {
        return response()->json([
            'pending_count' => ShiftChangeRequest::query()
                ->where('status', ShiftChangeRequest::STATUS_PENDING)
                ->count(),
        ]);
    }

    private function review(int $id, bool $approve, User $admin): RedirectResponse
    {
        $result = DB::transaction(function () use ($id, $approve, $admin): string {
            $shiftRequest = ShiftChangeRequest::query()->lockForUpdate()->findOrFail($id);

            if ($shiftRequest->status !== ShiftChangeRequest::STATUS_PENDING) {
                return 'handled';
            }

            $employee = User::query()->lockForUpdate()->find($shiftRequest->id_user);
            $shift = Shift::query()->find($shiftRequest->id_shift);

            if ($employee === null || $employee->role !== 'Kasir' || $shift === null) {
                return 'invalid';
            }

            if ($approve) {
                $employee->forceFill(['id_shift' => $shift->id_shift])->save();
            }

            $shiftRequest->update([
                'status' => $approve
                    ? ShiftChangeRequest::STATUS_APPROVED
                    : ShiftChangeRequest::STATUS_REJECTED,
                'id_admin' => $admin->id_user,
                'waktu_diproses' => now(),
            ]);

            return 'reviewed';
        });

        if ($result === 'handled') {
            return back()->with('error', 'Pengajuan shift ini sudah diproses sebelumnya.');
        }

        if ($result === 'invalid') {
            return back()->with('error', 'Data karyawan atau shift pada pengajuan ini sudah tidak tersedia.');
        }

        return back()->with(
            'status',
            $approve ? 'Pengajuan shift berhasil disetujui.' : 'Pengajuan shift berhasil ditolak.'
        );
    }
}
