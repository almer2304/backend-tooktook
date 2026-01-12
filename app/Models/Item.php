<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['store_id', 'name', 'description', 'stock', 'price_per_day'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
