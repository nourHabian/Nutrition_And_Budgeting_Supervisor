<?php

namespace App\Services;

use App\Models\User;
use App\Enums\PreferenceType;
use Carbon\Carbon;
use App\Models\Ingredient;
use App\Models\Meal;

class PlanGenerationService
{
    public function __construct(private CppPlannerService $cppPlanner) {}

    public function generate(User $user, array $data)
    {
        $input = $this->buildInputJson($user, $data);
                $outputPath = $this->cppPlanner->generate($input);

        if ($outputPath === null) {
            return null;
        }

        $response = app(PlanResponseService::class)->build(
            $outputPath,
            $data['days_per_meal'],
            $data['excluded_meals'] ?? []
        );

        @unlink($outputPath);

        return $response;
    }
    
    private function collectProfile(User $user): array
    {
        $profile = $user->familyProfile;

        if (!$profile) {
            return [
                'allergies' => [],
                'disliked_ingredients' => [],
                'liked_meals' => [],
                'disliked_meals' => [],
                'recent_meals' => [],
            ];
        }

        return [
            'allergies' => $profile->ingredientPreferences
                ->where('type', PreferenceType::ALLERGY)
                ->map(function ($preference) {
                    return [
                        'name' => $preference->ingredient->name,
                    ];
                })
                ->values()
                ->toArray(),

            'disliked_ingredients' => $profile->ingredientPreferences
                ->where('type', PreferenceType::DISLIKE)
                ->map(function ($preference) {
                    return [
                        'name' => $preference->ingredient->name,
                    ];
                })
                ->values()
                ->toArray(),

            'liked_meals' => $profile->mealPreferences
                ->where('type', PreferenceType::FAVORITE)
                ->map(function ($preference) {
                    return [
                        'meal_name' => $preference->meal->name,
                    ];
                })
                ->values()
                ->toArray(),

            'disliked_meals' => $profile->mealPreferences
                ->where('type', PreferenceType::DISLIKE)
                ->map(function ($preference) {
                    return [
                        'meal_name' => $preference->meal->name,
                    ];
                })
                ->values()
                ->toArray(),

            'recent_meals' => $user->mealHistories
                ->map(function ($history) {
                    $weeksAgo = max(
                        1,
                        Carbon::parse($history->last_eaten_at)
                            ->diffInWeeks(now())
                    );
                    return [
                        'meal_name' => $history->meal->name,
                        'weeks_ago' => $weeksAgo,
                    ];
                })
                ->filter(fn ($meal) => $meal['weeks_ago'] <= 4)
                ->values()
                ->toArray(),
        ];
    }

    private function collectInventory(User $user, array $data): array
    {
        $profile = $user->familyProfile;

        $alwaysAvailable = $profile->familyIngredients
            ->map(function ($item) {
                return [
                    'name' => $item->ingredient->name,
                ];
            })
            ->values();

        $alwaysAvailableNames = $alwaysAvailable
            ->pluck('name')
            ->toArray();

        $availableIngredients = [];

        foreach ($data['available_ingredients'] as $ingredient) {
            if (in_array($ingredient['name'], $alwaysAvailableNames)) {
                continue;
            }
            
            $ingredientModel = Ingredient::where(
                'name',
                $ingredient['name']
            )->first();

            $unit = $ingredientModel
                ->mealIngredients()
                ->first()?->unit;

            $availableIngredients[] = [
                'name' => $ingredientModel->name,
                'quantity' => $ingredient['quantity'],
                'unit' => $unit,
            ];
        }

        return [
            'available_ingredients' => $availableIngredients,
            'always_available_ingredients' => $alwaysAvailable->toArray(),
        ];
    }

    private function getCurrentSeason(): string
    {
        $today = now();

        $month = $today->month;
        $day = $today->day;

        return match (true) {
            // 21/3 - 20/6
            ($month == 3 && $day >= 21) || $month == 4 || $month == 5 || ($month == 6 && $day < 21)
                => 'الربيع',

            // 21/6 - 20/9
            ($month == 6 && $day >= 21) || $month == 7 || $month == 8 || ($month == 9 && $day < 21)
                => 'الصيف',

            // 21/9 - 20/12
            ($month == 9 && $day >= 21) || $month == 10 || $month == 11 || ($month == 12 && $day < 21)
                => 'الخريف',

            // 21/12 - 20/3
            default => 'الشتاء',
        };
    }

    private function collectConstraints(array $data): array
    {
        $budget = $data['budget'];
        if ($data['days_per_meal'] == 2) {
            $budget /= 2;
        }
        return [
            'prep_time' => $data['prep_time'],
            'season' => $this->getCurrentSeason(),
            'servings' => $data['servings'],
            'number_of_meals' => $data['number_of_meals'],
            'budget' => $budget,
            'plans_num' => 10,
        ];
    }

    private function buildInputJson(User $user, array $data): array
    {
        $profile = $this->collectProfile($user);

        if (!empty($data['excluded_meals'])) {
            $excludedMeals = Meal::whereIn(
                'dataset_id',
                $data['excluded_meals']
            )
            ->pluck('name')
            ->map(fn ($name) => [
                'meal_name' => $name,
            ])
            ->values()
            ->toArray();

            $profile['disliked_meals'] = array_merge(
                $profile['disliked_meals'],
                $excludedMeals
            );
        }

        // تجهيز required_meals
        $requiredMeals = collect($data['required_meals'] ?? [])
            ->map(function ($meal) {
                return [
                    'parent_id' => $meal['meal_id'],
                    'id' => $meal['expanded_meal_id'],
                ];
            })
            ->values()
            ->toArray();

        return [
            'profile' => $profile,
            'inventory' => $this->collectInventory($user, $data),
            'constraints' => $this->collectConstraints($data),
            'required_meals' => $requiredMeals,
        ];
    }
}