<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'image',
        'banner',
        'category_id',
        'business_name',
        'tax_id',
        'legal_type',
        'registered_address',
        'address',
        'support_email',
        'support_phone',
        'is_verified',
        'rating',
        'verification_date',
        'status'
    ];
    protected $with = ['user', 'storeSocials', 'banners', 'products', 'reviews'];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function storeSocials()
    {
        return $this->hasMany(StoreSocial::class, 'store_id');
    }

    public function banners()
    {
        return $this->hasMany(StoreBanner::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function reviews()
    {
        return $this->hasMany(StoreReview::class);
    }
}