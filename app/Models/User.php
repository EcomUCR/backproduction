<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, CanResetPassword;

    protected $fillable = [
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'profile_image_url',
        'status',
        'phone_number',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Si quieres seguir usando tu notificación personalizada:
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token, $this->email));
    }

    // Relaciones de tu modelo nuevo
    public function store()
    {
        return $this->hasOne(Store::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'customer_id');
    }

    public function productReviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function storeReviews()
    {
        return $this->hasMany(StoreReview::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}