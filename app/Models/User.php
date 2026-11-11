<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'ms_user';

    protected $primaryKey = 'id_user';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_user',
        'id_shift',
        'nama_lengkap',
        'email',
        'password',
        'no_telepon',
        'alamat',
        'foto_profil',
        'status',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function getFotoProfilUrlAttribute(): ?string
    {
        return $this->foto_profil
            ? asset('storage/'.ltrim($this->foto_profil, '/'))
            : null;
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'id_shift', 'id_shift');
    }

    public function getKategoriKaryawanAttribute(): string
    {
        return $this->shift?->kategori_karyawan ?? 'Belum ditentukan';
    }

    public function shiftChangeRequests(): HasMany
    {
        return $this->hasMany(ShiftChangeRequest::class, 'id_user', 'id_user');
    }
}
