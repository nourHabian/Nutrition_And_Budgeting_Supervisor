<?php

namespace App\Models;

use App\Enums\PrepTime;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'user_id',
        'number_of_meals',
        'budget',
        'prep_time',
        'estimated_cost',
        'days_per_meal',
        'servings',
        // 'accepted',
    ];

    protected $casts = [
        'prep_time' => PrepTime::class,
        // 'accepted' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function availableIngredients()
    {
        return $this->hasMany(PlanAvailableIngredient::class);
    }

    public function meals()
    {
        return $this->hasMany(PlanMeal::class);
    }

    public function shoppingListItems()
    {
        return $this->hasMany(ShoppingListItem::class);
    }
}
