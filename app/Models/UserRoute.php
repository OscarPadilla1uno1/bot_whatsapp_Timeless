<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'origin_lat',
        'origin_lng',
        'destination_lat',
        'destination_lng',
        'origin_address',
        'destination_address',
        'route_data'
    ];

    protected $casts = [
        'route_data' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}