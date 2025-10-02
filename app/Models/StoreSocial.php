<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSocial extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'platform',
        'url'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}