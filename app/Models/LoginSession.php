<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginSession extends Model
{
    protected $table = 'tr_sesi_login';

    protected $primaryKey = 'id_sesi';

    public $timestamps = false;

    protected $fillable = [
        'nama_user',
        'id_shift',
        'waktu_login',
        'waktu_logout',
    ];
}
