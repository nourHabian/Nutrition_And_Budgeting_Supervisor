<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;

class PlanHistoryService
{
    public function index(User $user): array
    {
        return $user->plans()
            ->latest()
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'created_at' => $plan->created_at->toDateString(),
                    'estimated_cost' => $plan->estimated_cost,
                    'budget' => $plan->budget,
                    'number_of_meals' => $plan->number_of_meals,
                    'days_per_meal' => $plan->days_per_meal,
                    'servings' => $plan->servings,
                    'prep_time' => $plan->prep_time,
                ];
            })
            ->toArray();
    }

    public function show(User $user, Plan $plan): array
    {
        if ($plan->user_id !== $user->id) {
            abort(403, 'لا يمكنك الوصول إلى هذه الخطة.');
        }

        $plan->load([
            'meals.meal',
            'meals.ingredients.ingredient',
            'shoppingListItems.ingredient',
        ]);

        return [
            'id' => $plan->id,
            'created_at' => $plan->created_at->toDateString(),
            'budget' => $plan->budget,
            'estimated_cost' => $plan->estimated_cost,
            'number_of_meals' => $plan->number_of_meals,
            'days_per_meal' => $plan->days_per_meal,
            'servings' => $plan->servings,
            'prep_time' => $plan->prep_time,

            'meals' => $plan->meals
                ->sortBy('day')
                ->values()
                ->map(function ($planMeal) {

                    return [
                        'day' => $planMeal->day,
                        'meal_id' => $planMeal->meal->dataset_id,
                        'name' => $planMeal->meal->name,
                        'estimated_cost' => $planMeal->estimated_cost,

                        'ingredients' => $planMeal->ingredients
                            ->map(function ($ingredient) {

                                return [
                                    'id' => $ingredient->ingredient->dataset_id,
                                    'name' => $ingredient->ingredient->name,
                                    'quantity' => $ingredient->quantity,
                                    'unit' => $ingredient->unit,
                                ];
                            })
                            ->values(),
                    ];
                }),

            'shopping_list' => $plan->shoppingListItems
                ->map(function ($item) {

                    return [
                        'ingredient_id' => $item->ingredient->dataset_id,
                        'name' => $item->ingredient->name,
                        'required_quantity' => $item->required_quantity,
                        'available_quantity' => $item->available_quantity,
                        'unit' => $item->unit,
                        'estimated_price' => $item->estimated_price,
                    ];
                })
                ->values(),
        ];
    }
}