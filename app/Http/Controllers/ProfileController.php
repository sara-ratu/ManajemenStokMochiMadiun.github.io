<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load('shift');
        $latestShiftRequest = $user->shiftChangeRequests()
            ->with('shift')
            ->latest('id_pengajuan')
            ->first();

        return view('profile.show', [
            'user' => $user,
            'latestShiftRequest' => $latestShiftRequest,
            'requestableShifts' => Shift::query()
                ->whereIn('nama_shift', ['Pagi', 'Siang'])
                ->orderBy('nama_shift')
                ->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validateWithBag('profile', [
            'nama_lengkap' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:45', Rule::unique('ms_user', 'email')->ignore($user->getKey(), 'id_user')],
            'no_telepon' => ['nullable', 'string', 'max:15'],
            'alamat' => ['nullable', 'string', 'max:100'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
        ]);

        $user->fill($data)->save();

        return redirect()->route('profile.show')->with('status', 'Profil berhasil diperbarui.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('photo', [
            'foto_profil' => ['nullable', 'required_without:hapus_foto_profil', 'image', 'max:2048'],
            'hapus_foto_profil' => ['nullable', 'boolean'],
        ], [
            'foto_profil.required_without' => 'Pilih foto profil yang ingin diunggah.',
            'foto_profil.image' => 'File foto profil harus berupa gambar yang valid.',
            'foto_profil.max' => 'Ukuran foto profil maksimal 2 MB.',
        ]);

        $user = $request->user();
        $oldPhoto = $user->foto_profil;
        if ($request->hasFile('foto_profil')) {
            $photo = $request->file('foto_profil')->store('profiles', 'public');
            if ($photo === false || Str::length($photo) > 255) {
                throw new RuntimeException('Foto profil gagal disimpan. Silakan coba lagi.');
            }
            $user->foto_profil = $photo;
        } elseif ($request->boolean('hapus_foto_profil')) {
            $user->foto_profil = null;
        }

        $user->save();
        if ($oldPhoto && $oldPhoto !== $user->foto_profil && ! Storage::disk('public')->delete($oldPhoto)) {
            logger()->warning('Failed to delete a profile photo from public storage.', ['path' => $oldPhoto]);
        }

        return redirect()->route('profile.show')->with('status', 'Foto profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $request->user()->forceFill(['password' => $data['password']])->save();

        return redirect()->route('profile.show')->with('status', 'Password berhasil diperbarui.');
    }
}
