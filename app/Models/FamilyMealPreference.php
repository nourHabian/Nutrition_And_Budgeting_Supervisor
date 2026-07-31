<?php

namespace App\Models;

use App\Enums\PreferenceType;
use Illuminate\Database\Eloquent\Model;

class FamilyMealPreference extends Model
{
    protected $fillable = [
        'family_profile_id',
        'meal_id',
        'type'
    ];

    protected $casts = [
        'type' => PreferenceType::class,
    ];

    public function familyProfile()
    {
        return $this->belongsTo(FamilyProfile::class);
    }

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }
}
