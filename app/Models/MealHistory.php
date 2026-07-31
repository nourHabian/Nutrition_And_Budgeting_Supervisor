<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealHistory extends Model
{
    protected $fillable = [
        'user_id',
        'meal_id',
        'last_eaten_at',
    ];

    protected $casts = [
        'last_eaten_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function meal()
    {
        return $this->belongsTo(
            Meal::class
        );
    }
}