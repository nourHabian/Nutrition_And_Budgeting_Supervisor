<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyIngredient extends Model
{
    protected $fillable = [
        'family_profile_id',
        'ingredient_id'
    ];

    public function familyProfile()
    {
        return $this->belongsTo(FamilyProfile::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
