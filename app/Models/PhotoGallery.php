<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoGallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'photo_url',
        'caption',
        'taken_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
