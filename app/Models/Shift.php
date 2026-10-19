<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $table = 'ms_shift';

    protected $primaryKey = 'id_shift';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
