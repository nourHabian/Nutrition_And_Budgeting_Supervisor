<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingListItem extends Model
{
    protected $fillable = [
        'plan_id',
        'ingredient_id',
        'required_quantity',
        'available_quantity',
        'unit',
        'estimated_price',
    ];

    public function plan()
    {
        return $this->belongsTo(
            Plan::class
        );
    }

    public function ingredient()
    {
        return $this->belongsTo(
            Ingredient::class
        );
    }

}