<?php

namespace App\Services;

use App\Models\FamilyIngredient;
use App\Models\FamilyIngredientPreference;
use App\Models\FamilyMealPreference;
use App\Models\FamilyProfile;
use App\Models\Ingredient;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilySetupService
{
    public function save(User $user, array $data)
    {
        DB::transaction(function () use ($user, $data) {
            $profile = FamilyProfile::updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'family_members' => $data['family_members'],
                ]
            );
            $ingredients = Ingredient::pluck('id', 'name');
            $meals = Meal::pluck('id', 'name');

            FamilyIngredient::where('family_profile_id', $profile->id)->delete();
            FamilyIngredientPreference::where('family_profile_id', $profile->id)->delete();
            FamilyMealPreference::where('family_profile_id', $profile->id)->delete();

            foreach ($data['always_available_ingredients'] ?? [] as $ingredientName) {
                FamilyIngredient::create([
                    'family_profile_id' => $profile->id,
                    'ingredient_id' => $ingredients[$ingredientName]
                ]);
            }

            foreach ($data['allergic_ingredients'] ?? [] as $ingredientName) {
                FamilyIngredientPreference::create([
                    'family_profile_id' => $profile->id,
                    'ingredient_id' => $ingredients[$ingredientName],
                    'type' => 'allergy'
                ]);
            }

            foreach ($data['disliked_ingredients'] ?? [] as $ingredientName) {
                FamilyIngredientPreference::create([
                    'family_profile_id' => $profile->id,
                    'ingredient_id' => $ingredients[$ingredientName],
                    'type' => 'dislike'
                ]);
            }

            foreach ($data['favorite_meals'] ?? [] as $mealName) {
                FamilyMealPreference::create([
                    'family_profile_id' => $profile->id,
                    'meal_id' => $meals[$mealName],
                    'type' => 'favorite'
                ]);
            }

            foreach ($data['disliked_meals'] ?? [] as $mealName) {
                FamilyMealPreference::create([
                    'family_profile_id' => $profile->id,
                    'meal_id' => $meals[$mealName],
                    'type' => 'dislike'
                ]);
            }

            return $profile;
        });
    }

    public function update(User $user, array $data)
    {
        return $this->save($user, $data);
    }
}