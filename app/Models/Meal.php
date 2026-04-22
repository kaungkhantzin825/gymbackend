<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'photo_path', 'notes', 'total_calories',
        'total_protein', 'total_carbs', 'total_fat', 'meal_time'
    ];

    protected $casts = [
        'meal_time' => 'datetime',
    ];

    protected $appends = ['photo_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function foodLogs()
    {
        return $this->hasMany(FoodLog::class);
    }

    /**
     * Get the full photo URL
     */
    public function getPhotoUrlAttribute()
    {
        if (!$this->photo_path) {
            return null;
        }
        return url('storage/' . $this->photo_path);
    }

    /**
     * Recalculate meal totals from food logs
     */
    public function recalculateTotals()
    {
        $this->total_calories = $this->foodLogs()->sum('calories');
        $this->total_protein = $this->foodLogs()->sum('protein');
        $this->total_carbs = $this->foodLogs()->sum('carbs');
        $this->total_fat = $this->foodLogs()->sum('fat');
        $this->save();
    }
}
