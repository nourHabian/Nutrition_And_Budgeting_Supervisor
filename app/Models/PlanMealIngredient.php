<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanMealIngredient extends Model
{
    protected $fillable = [
        'plan_meal_id',
        'ingredient_id',
        'quantity',
        'unit',
        'source',
    ];

    public function planMeal()
    {
        return $this->belongsTo(
            PlanMeal::class
        );
    }

    public function ingredient()
    {
        return $this->belongsTo(
            Ingredient::class
        );
    }
}