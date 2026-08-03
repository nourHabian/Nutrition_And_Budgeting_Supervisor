<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanMeal extends Model
{
    protected $fillable = [
        'plan_id',
        'meal_id',
        'expanded_meal_id',
        'estimated_cost',
        'day',
    ];


    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }

    public function ingredients()
    {
        return $this->hasMany(PlanMealIngredient::class);
    }

}
