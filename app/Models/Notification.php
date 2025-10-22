<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'type',
        'title',
        'message',
        'related_id',
        'related_type',
        'is_read',
        'is_archived',
        'priority',
        'data',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_archived' => 'boolean',
        'data' => 'array',
    ];

    // 🔹 Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔹 Relación polimórfica
    public function related()
    {
        return $this->morphTo(null, 'related_type', 'related_id');
    }
}
