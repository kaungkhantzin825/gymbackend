<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_weight',
        'target_weight',
        'height',
        'age',
        'gender',
        'fitness_goal'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
