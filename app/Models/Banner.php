<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'character',
        'image',
        'link',
        'btn_text',
        'btn_color',
        'type',
        'orientation',
        'position',
        'is_active',
    ];

    /**
     * Casts automáticos para mejorar legibilidad y tipos.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];
}
