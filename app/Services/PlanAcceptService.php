<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Meal;
use App\Models\MealHistory;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlanAcceptService
{
    public function accept(User $user, array $data): Plan
    {
        return DB::transaction(function () use ($user, $data) {
            $plan = $this->createPlan($user, $data);
            $this->saveAvailableIngredients($plan, $data);
            $this->saveMeals($plan, $data);
            $this->generateShoppingList($plan);
            $this->updateMealHistory($user, $plan);
            return $plan->fresh();
        });
    }

    private function createPlan(User $user, array $data): Plan
    {
        return Plan::create([
            'user_id' => $user->id,
            'number_of_meals' => $data['number_of_meals'],
            'budget' => $data['budget'],
            'prep_time' => $data['prep_time'],
            'estimated_cost' => $data['total_cost'],
            'days_per_meal' => $data['days_per_meal'],
            'servings' => $data['servings'],
        ]);
    }

    private function saveAvailableIngredients(Plan $plan, array $data): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $familyProfile = $user->familyProfile;

        $alwaysAvailableIds = $familyProfile
            ->familyIngredients()
            ->pluck('ingredient_id')
            ->toArray();

        foreach ($data['available_ingredients'] as $ingredient) {
            $ingredientModel = Ingredient::where(
                'name',
                $ingredient['name']
            )->first();
            if (in_array($ingredientModel->id, $alwaysAvailableIds)) {
                continue;
            }
            $plan->availableIngredients()->create([
                'ingredient_id' => $ingredientModel->id,
                'quantity' => $ingredient['quantity'],
            ]);
        }
    }

    private function saveMeals(Plan $plan, array $data): void
    {
        $day = 1;
        foreach ($data['meals'] as $meal) {
            $mealModel = Meal::where(
                'dataset_id',
                $meal['meal_id']
            )->first();

            $planMeal = $plan->meals()->create([
                'meal_id' => $mealModel->id,
                'expanded_meal_id' => $meal['expanded_meal_id'],
                'estimated_cost' => $meal['estimated_cost'],
                'day' => $day,
            ]);

            foreach ($meal['ingredients'] as $ingredient) {
                $ingredientModel = Ingredient::where(
                    'dataset_id',
                    $ingredient['id']
                )->first();

                $planMeal->ingredients()->create([
                    'ingredient_id' => $ingredientModel->id,
                    'quantity' => $ingredient['quantity'],
                    'unit' => $ingredient['unit'],
                ]);
            }
            $day++;
        }
    }

    private function generateShoppingList(Plan $plan): void
    {
        $plan->load([
            'meals.ingredients',
            'availableIngredients',
            'user.familyProfile.familyIngredients',
        ]);

        $requiredIngredients = [];

        foreach ($plan->meals as $planMeal) {
            foreach ($planMeal->ingredients as $ingredient) {
                $id = $ingredient->ingredient_id;
                if (!isset($requiredIngredients[$id])) {
                    $requiredIngredients[$id] = [
                        'quantity' => 0,
                        'unit' => $ingredient->unit,
                    ];
                }
                $requiredIngredients[$id]['quantity'] += $ingredient->quantity;
            }
        }

        foreach ($plan->availableIngredients as $available) {
            if (!isset($requiredIngredients[$available->ingredient_id])) {
                continue;
            }
            $requiredIngredients[$available->ingredient_id]['quantity']
                -= $available->quantity;
        }

        $alwaysAvailable = $plan->user
            ->familyProfile
            ->familyIngredients
            ->pluck('ingredient_id')
            ->toArray();

        foreach ($requiredIngredients as $ingredientId => $data) {
            if (in_array($ingredientId, $alwaysAvailable)) {
                continue;
            }
            if ($data['quantity'] <= 0) {
                continue;
            }
            $ingredient = Ingredient::find($ingredientId);
            $estimatedPrice = (int) ceil(
                $ingredient->price * $data['quantity']
            );
            $availableQuantity = 0;
            $available = $plan->availableIngredients
                ->firstWhere('ingredient_id', $ingredientId);
            if ($available) {
                $availableQuantity = $available->quantity;
            }
            $plan->shoppingListItems()->create([
                'ingredient_id' => $ingredientId,
                'required_quantity' => $data['quantity'],
                'available_quantity' => $availableQuantity,
                'unit' => $data['unit'],
                'estimated_price' => $estimatedPrice,
            ]);
        }
    }

    private function updateMealHistory(User $user, Plan $plan): void
    {
        foreach ($plan->meals as $planMeal) {
            MealHistory::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'meal_id' => $planMeal->meal_id,
                ],
                [
                    'last_eaten_at' => now(),
                ]
            );
        }
    }
}