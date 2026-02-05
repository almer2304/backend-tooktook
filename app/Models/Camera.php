<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    protected $fillable = ['name', 'description', 'stock', 'price_per_day'];

    public function rental()
    {
        return $this->hasMany(Rental::class, 'cameras_id');
    }
}
