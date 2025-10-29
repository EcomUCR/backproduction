<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'name',
        'email',
        'subject',
        'description',
        'status',
        'admin_notes',
        'read',
    ];

    /**
     * Relación: un reporte puede pertenecer a un pedido
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Accesor simple: formatear el estado visualmente si lo necesitás en el front
     */
    public function getFormattedStatusAttribute()
    {
        return match ($this->status) {
            'PENDING' => 'Pendiente',
            'IN_REVIEW' => 'En revisión',
            'RESOLVED' => 'Resuelto',
            'REJECTED' => 'Rechazado',
            default => 'Desconocido',
        };
    }
}
