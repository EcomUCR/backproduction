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
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // Inversa estándar
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // (Opcional) Atajo para filtrar la principal
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
