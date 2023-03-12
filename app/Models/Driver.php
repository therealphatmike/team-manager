<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'firstName',
        'lastName',
        'email',
        'teamId',
    ];

    protected $casts = [
        'email' => 'encrypted',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'teamId');
    }
}
