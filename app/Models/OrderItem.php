<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'store_id',
        'quantity',
        'unit_price',
        'discount_pct',
    ];

    // 🔹 Cada item pertenece a una orden
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // 🔹 Cada item pertenece a un producto
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // 🔹 Cada item pertenece a una tienda (opcional)
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // 🔹 Precio total del ítem (unitario * cantidad)
    public function getTotalAttribute()
    {
        return ($this->unit_price ?? 0) * ($this->quantity ?? 1);
    }
}
