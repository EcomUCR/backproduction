<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialMedia extends Model
{
    protected $fillable = ['vendor_id', 'type', 'url'];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
