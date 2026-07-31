<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientMeal;
use App\Models\Meal;
use Illuminate\Support\Facades\DB;

class DatasetImporterService
{
    private string $ingredientsFile;
    private string $mealsFile;

    public function __construct()
    {
        $this->ingredientsFile = storage_path(
            'datasets/ingredients_dataset.csv'
        );

        $this->mealsFile = storage_path(
            'datasets/dish_dataset.csv'
        );
    }

    private function readCsv(string $path): array
    {
        $rows = [];
        if (!file_exists($path)) {
            return [];
        }
        $file = fopen($path, 'r');
        $header = fgetcsv($file);
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        while (($row = fgetcsv($file)) !== false) {
            $rows[] = array_combine($header, $row);
        }
        fclose($file);
        return $rows;
    }

    public function importIngredients()
    {
        $rows = $this->readCsv($this->ingredientsFile);
        foreach ($rows as $row) {
            Ingredient::updateOrCreate(
                [
                    'dataset_id' => $row['ingredient_id']
                ],
                [
                    'name' => $row['name'],
                    'protein' => $row['protein'],
                    'carbohydrates' => $row['carbohydrates'],
                    'fiber' => $row['fiber'],
                    'price' => $row['price'],
                ]
            );
        }
        return count($rows);
    }

    public function importMeals()
    {
        $rows = $this->readCsv($this->mealsFile);
        $imported = [];
        foreach ($rows as $row) {
            $recipeId = $row['recipe_id'];
            if (!isset($imported[$recipeId])) {
                Meal::updateOrCreate(
                    [
                        'dataset_id' => $recipeId
                    ],
                    [
                        'name' => $row['recipe_name'],
                        'servings' => $row['servings'],
                        'prep_time' => $row['prep_time'],
                        'difficulty_level' => $row['difficulty_level'],
                        'seasonality' => $row['seasonality'],
                    ]
                );
                $imported[$recipeId] = true;
            }
        }
        return count($imported);
    }

    public function importMealIngredients()
    {
        $rows = $this->readCsv($this->mealsFile);
        $meals = Meal::all()->keyBy('dataset_id');
        $ingredients = Ingredient::all()->keyBy(function ($ingredient) {
            return trim($ingredient->name);
        });
        $count = 0;
        foreach ($rows as $row) {
            $meal = $meals->get($row['recipe_id']);
            $ingredient = $ingredients->get(
                trim($row['ingredient_name'])
            );
            if (!$meal || !$ingredient) {
                continue;
            }
            $substituteId = null;
            if (!empty($row['substitution'])) {
                $substitute = $ingredients->get(
                    trim($row['substitution'])
                );
                if ($substitute) {
                    $substituteId = $substitute->id;
                }
            }
            IngredientMeal::updateOrCreate(
                [
                    'meal_id' => $meal->id,
                    'ingredient_id' => $ingredient->id,
                ],
                [
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit'],
                    'necessity_level' => $row['necessity_level'],
                    'substitute_ingredient_id' => $substituteId,
                ]
            );
            $count++;
        }
        return $count;
    }

    public function importAll()
    {
        DB::transaction(function () {
            $this->importIngredients();
            $this->importMeals();
            $this->importMealIngredients();
        });
    }

}