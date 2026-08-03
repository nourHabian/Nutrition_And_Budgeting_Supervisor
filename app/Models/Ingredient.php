<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = [
        'dataset_id',
        'name',
        'protein',
        'carbohydrates',
        'fiber',
        'price'
    ];

    protected $casts = [
        'price' => 'float',
        'protein' => 'float',
        'carbohydrates' => 'float',
        'fiber' => 'float',
    ];

    public function meals()
    {
        return $this->belongsToMany(Meal::class)
            ->withPivot([
                'quantity',
                'unit',
                'necessity_level',
                'substitution'
            ])
            ->withTimestamps();
    }

    public function mealIngredients()
    {
        return $this->hasMany(IngredientMeal::class);
    }

    public function familyIngredients()
    {
        return $this->hasMany(FamilyIngredient::class);
    }

    public function familyPreferences()
    {
        return $this->hasMany(
            FamilyIngredientPreference::class
        );
    }

    public function planAvailableIngredients()
    {
        return $this->hasMany(
            PlanAvailableIngredient::class
        );
    }

    public function planMealIngredients()
    {
        return $this->hasMany(
            PlanMealIngredient::class
        );
    }

    public function shoppingListItems()
    {
        return $this->hasMany(
            ShoppingListItem::class
        );
    }
}
