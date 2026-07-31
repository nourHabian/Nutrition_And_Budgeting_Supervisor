<?php

namespace App\Http\Controllers\Api;

use App\Enums\PreferenceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\FamilySetupRequest;
use App\Services\FamilySetupService;
use Illuminate\Support\Facades\Auth;

class FamilySetupController extends Controller
{
    public function store(FamilySetupRequest $request, FamilySetupService $service)
    {
        $service->save(
            Auth::user(),
            $request->validated()
        );
        return response()->json([
            'message' => 'تم حفظ المعلومات بنجاح'
        ]);
    }

    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $profile = $user->familyProfile()->with([
            'familyIngredients.ingredient',
            'ingredientPreferences.ingredient',
            'mealPreferences.meal'
        ])->first();

        if (!$profile) {
            return response()->json(['message' => 'لم يتم العثور على معلومات العائلة'], 404);
        }

        return response()->json([
            'family_size' => 
                $profile->family_members,
            'always_available_ingredients' => 
                $profile->familyIngredients->pluck('ingredient.name'),
            'allergic_ingredients' => 
                $profile->ingredientPreferences->where('type', PreferenceType::ALLERGY)->pluck('ingredient.name'),
            'disliked_ingredients' =>
                $profile->ingredientPreferences->where('type', PreferenceType::DISLIKE)->pluck('ingredient.name'),
            'favorite_meals' =>
                $profile->mealPreferences->where('type', PreferenceType::FAVORITE)->pluck('meal.name'),
            'disliked_meals' =>
                $profile->mealPreferences->where('type', PreferenceType::DISLIKE)->pluck('meal.name'),
        ]);
    }

    public function update(FamilySetupRequest $request, FamilySetupService $service)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $service->update(
            $user,
            $request->validated()
        );

        return response()->json(['message' => 'تم تعديل معلومات العائلة بنجاح']);
    }

}