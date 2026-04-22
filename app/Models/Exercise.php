<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exercise extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'exercise_name', 'exercise_type', 'duration_minutes',
        'calories_burned', 'sets', 'reps', 'weight', 'notes', 'exercise_time'
    ];

    protected $casts = [
        'exercise_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
