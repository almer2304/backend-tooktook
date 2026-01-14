<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

class Rental extends Model
{
    protected $fillable = ['user_id', 'camera_id', 'start_date', 'due_date', 'returned_at', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function camera()
    {
        return $this->belongsTo(Camera::class, 'cameras_id');
    }
}
