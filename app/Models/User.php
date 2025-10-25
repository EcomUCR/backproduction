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

    /**
     * Campos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'image',
        'status',
        'phone_number',
        'role',
    ];

    /**
     * Campos ocultos al serializar el modelo.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts automáticos de atributos.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Notificación personalizada de restablecimiento de contraseña.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token, $this->email));
    }

    /**
     * Relaciones del usuario con otras entidades.
     */
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
        return $this->hasMany(Address::class, 'user_id');
    }

    public function productReviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function storeReviews()
    {
        return $this->hasMany(related: StoreReview::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

}
