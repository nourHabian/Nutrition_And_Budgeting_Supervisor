<?php

namespace App\Models;

use App\Enums\PrepTime;
use App\Enums\Seasonality;
use App\Enums\DifficultyLevel;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    protected $fillable = [
        'dataset_id',
        'name',
        'servings',
        'prep_time',
        'difficulty_level',
        'seasonality',
    ];

    protected $casts = [
        'prep_time' => PrepTime::class,
        'seasonality' => Seasonality::class,
        'difficulty_level' => DifficultyLevel::class,
    ];

    public function mealIngredients()
    {
        return $this->hasMany(IngredientMeal::class);
    }

    public function familyPreferences()
    {
        return $this->hasMany(
            FamilyMealPreference::class
        );
    }

    public function planMeals()
    {
        return $this->hasMany(
            PlanMeal::class
        );
    }

    public function histories()
    {
        return $this->hasMany(
            MealHistory::class
        );
    }
}
