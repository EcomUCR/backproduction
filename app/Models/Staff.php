<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'role',
        'phone_number',
        'position',
        'notes',
    ];

    // Relation to the User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods for roles
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }
}
