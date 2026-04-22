<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkoutSet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workout_id',
        'exercise_id',
        'set_number',
        'reps',
        'weight',
        'rpe',
        'rest_time_seconds',
        'is_superset',
    ];

    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }
}
