<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_id', 'food_name', 'fatsecret_food_id', 'brand_name',
        'serving_size', 'serving_unit', 'calories', 'protein', 'carbs',
        'fat', 'fiber', 'sugar', 'sodium'
    ];

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }
}
