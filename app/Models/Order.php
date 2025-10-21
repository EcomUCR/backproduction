<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'subtotal',
        'shipping',
        'taxes',
        'total',
        'address_id',
        'street',
        'city',
        'state',
        'zip_code',
        'country',
        'payment_method',
        'payment_id',
    ];

    // ✅ Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function items()
    {
        // 🔹 Aseguramos que la llave foránea esté explícita
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
