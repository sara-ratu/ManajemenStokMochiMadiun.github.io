<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $table = 'ms_produk';

    protected $primaryKey = 'id_produk';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_kategori', 'id_kategori');
    }

    public function getGambarUrlAttribute(): ?string
    {
        if (blank($this->gambar)) {
            return null;
        }

        if (Str::startsWith($this->gambar, ['http://', 'https://'])) {
            return $this->gambar;
        }

        return '/storage/'.ltrim($this->gambar, '/');
    }
}
