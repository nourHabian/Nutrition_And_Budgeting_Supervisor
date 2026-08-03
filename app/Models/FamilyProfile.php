<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'family_members',
        // 'budget',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function familyIngredients()
    {
        return $this->hasMany(FamilyIngredient::class);
    }

    public function ingredientPreferences()
    {
        return $this->hasMany(FamilyIngredientPreference::class);
    }

    public function mealPreferences()
    {
        return $this->hasMany(FamilyMealPreference::class);
    }


}
