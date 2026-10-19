<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load('shift');
        $isCashier = $user->role === 'Kaisr';

        return view('profile.show', compact('user', 'isCashier'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:45', Rule::unique('ms_user', 'email')->ignore($user->getKey(), 'id_user')],
            'no_telepon' => ['nullable', 'string', 'max:15'],
            'alamat' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'current_password' => ['required_with:password', 'current_password'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'current_password.required_with' => 'Password saat ini wajib diisi untuk mengganti password.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
        ]);

        unset($data['current_password']);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->fill($data)->save();

        return redirect()->route('profile.show')->with('status', 'Profil berhasil diperbarui.');
    }
}
