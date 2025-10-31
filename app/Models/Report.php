<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_number',
        'order_id',
        'name',
        'email',
        'subject',
        'description',
        'images',
        'status',
        'admin_notes',
        'read',
    ];

    protected $casts = [
        'images' => 'array',
        'read' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
