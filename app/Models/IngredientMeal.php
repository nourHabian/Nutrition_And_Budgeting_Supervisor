<?php

namespace App\Models;

use App\Enums\NecessityLevel;
use Illuminate\Database\Eloquent\Model;

class IngredientMeal extends Model
{
    protected $table = 'ingredient_meal';

    protected $fillable = [
        'meal_id',
        'ingredient_id',
        'quantity',
        'unit',
        'necessity_level',
        'substitute_ingredient_id',
    ];

    protected $casts = [
        'necessity_level' => NecessityLevel::class,
    ];
    

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function substituteIngredient()
    {
        return $this->belongsTo(
            Ingredient::class,
            'substitute_ingredient_id'
        );
    }
}