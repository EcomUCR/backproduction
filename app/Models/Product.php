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
    ];

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

    public function getTotalSoldAttribute()
    {
        return $this->orderItems()->sum('quantity');
    }
}
