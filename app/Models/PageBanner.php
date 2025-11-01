<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageBanner extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla.
     */
    protected $table = 'page_banners';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'page_name',
        'slot_number',
        'banner_id',
    ];

    /**
     * Relación: un PageBanner pertenece a un Banner.
     */
    public function banner()
    {
        return $this->belongsTo(Banner::class, 'banner_id');
    }
}
