<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftChangeRequest extends Model
{
    public const STATUS_PENDING = 'Menunggu';

    public const STATUS_APPROVED = 'Disetujui';

    public const STATUS_REJECTED = 'Ditolak';

    protected $table = 'tr_pengajuan_shift';

    protected $primaryKey = 'id_pengajuan';

    protected $fillable = [
        'id_user',
        'id_shift',
        'status',
        'id_admin',
        'waktu_diproses',
    ];

    protected function casts(): array
    {
        return ['waktu_diproses' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'id_shift', 'id_shift');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_admin', 'id_user');
    }
}
