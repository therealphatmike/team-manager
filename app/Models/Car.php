<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'number',
        'teamId',
        'driverId',
    ];

    protected $guarded = [
        'teamId',
    ];

    public function driver()
    {
        return $this->hasOne(Driver::class, 'carId');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'teamId');
    }
}
