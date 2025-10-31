<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'sku',
        'name',
        'description',
        'details',
        'price',
        'discount_price',
        'stock',
        'sold_count',
        'status',
        'is_featured',
        'image_1_url',
        'image_2_url',
        'image_3_url',
        'rating', // 🆕 Campo agregado
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'rating' => 'float', // 🧮 Se asegura de devolver decimal
    ];

    // 🔹 Relaciones
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // 🔹 Accesor dinámico
    public function getTotalSoldAttribute()
    {
        return $this->orderItems()->sum('quantity');
    }

    // 🧠 Nuevo método: recalcular rating promedio
    public function updateRatingFromReviews(): void
    {
        $average = $this->reviews()->avg('rating') ?? 0;
        $this->update(['rating' => round($average, 1)]);
    }
}
