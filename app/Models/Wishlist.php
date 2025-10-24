<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'slug', 'is_public'];

    protected static function booted()
    {
        static::creating(function ($wishlist) {
            if (empty($wishlist->slug)) {
                $wishlist->slug = (string) Str::uuid();
            }

            if (is_null($wishlist->is_public)) {
                $wishlist->is_public = true;
            }
        });
    }

    public function items()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
