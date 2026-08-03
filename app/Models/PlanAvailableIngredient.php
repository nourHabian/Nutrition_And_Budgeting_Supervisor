<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanAvailableIngredient extends Model
{
    protected $fillable = [
        'plan_id',
        'ingredient_id',
        'quantity',
    ];


    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }


    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
