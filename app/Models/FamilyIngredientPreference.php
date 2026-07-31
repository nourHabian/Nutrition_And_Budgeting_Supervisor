<?php

namespace App\Models;

use App\Enums\PreferenceType;
use Illuminate\Database\Eloquent\Model;

class FamilyIngredientPreference extends Model
{
    protected $fillable = [
        'family_profile_id',
        'ingredient_id',
        'type'
    ];

    protected $casts = [
        'type' => PreferenceType::class,
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
