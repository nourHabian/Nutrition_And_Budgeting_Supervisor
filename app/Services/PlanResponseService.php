<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Meal;

class PlanResponseService
{
    public function build(string $outputPath, int $daysPerMeal = 1, array $excludedMeals = []): array|null
    {
        if (!file_exists($outputPath)) {
            return null;
        }

        $generatedMeals = json_decode(
            file_get_contents($outputPath),
            true
        );

        if (!is_array($generatedMeals)) {
            throw new \Exception('فشل قراءة output.json');
        }

        $response = [];
        $totalCost = 0;

        foreach ($generatedMeals as $mealData) {
            $meal = Meal::where('dataset_id', $mealData['meal_id'])->first();
            if (!$meal) continue;
            $ingredients = [];

            foreach ($mealData['selected_ingredients'] as $ingredientData) {
                $ingredient = Ingredient::where(
                    'dataset_id',
                    $ingredientData['ingredient_id']
                )->first();

                $ingredients[] = [
                    'id' => $ingredientData['ingredient_id'],
                    'name' => $ingredient?->name,
                    'quantity' => $ingredientData['quantity'],
                    'unit' => $ingredientData['unit'],
                ];
            }

            $response[] = [
                'meal_id' => $meal->dataset_id,
                'expanded_meal_id' => $mealData['expanded_meal_id'],
                'name' => $meal->name,
                'prep_time' => $meal->prep_time->value,
                'difficulty' => $meal->difficulty_level->value,
                'seasonality' => $meal->seasonality->value,
                'estimated_cost' => $mealData['estimated_cost'],
                'ingredients' => $ingredients,
            ];

            $totalCost += $mealData['estimated_cost'];
        }

        if ($daysPerMeal === 2) {
            $totalCost *= 2;
            $response = $this->doubleMeals($response);
        }

        return [
            'total_cost' => $totalCost,
            'meals' => $response,
            'excluded_meals' => $excludedMeals,
        ];
    }

    private function doubleMeals(array $response): array
    {
        foreach ($response as &$meal) {
            $meal['estimated_cost'] *= 2;
            foreach ($meal['ingredients'] as &$ingredient) {
                $ingredient['quantity'] *= 2;
            }
        }
        return $response;
    }
}