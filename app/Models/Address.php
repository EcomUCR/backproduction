<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number',
        'street',
        'city',
        'state',
        'zip_code',
        'country',
        'is_default'
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}