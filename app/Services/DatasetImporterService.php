<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientMeal;
use App\Models\Meal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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

    public function exportIngredientsJson()
    {
        $ingredients = Ingredient::orderBy('dataset_id')->get([
                'dataset_id',
                'name',
                'protein',
                'carbohydrates',
                'fiber',
                'price'
            ]);

        $data = [];

        foreach ($ingredients as $ingredient) {
            $data[] = [
                'ingredient_id' => $ingredient->dataset_id,
                'name' => $ingredient->name,
                'protein' => $ingredient->protein,
                'carbohydrates' => $ingredient->carbohydrates,
                'fiber' => $ingredient->fiber,
                'price' => $ingredient->price,
            ];
        }

        File::put(
            base_path('cpp/ingredients.json'),
            json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }

    public function exportRecipesJson()
    {
        $meals = Meal::with([
            'mealIngredients.ingredient',
            'mealIngredients.substituteIngredient'
        ])->orderBy('dataset_id')->get();

        $recipes = [];
        foreach ($meals as $meal) {
            $ingredients = [];
            foreach ($meal->mealIngredients as $mealIngredient) {
                $ingredients[] = [
                    'name' => $mealIngredient->ingredient->name,
                    'quantity' => (int) $mealIngredient->quantity,
                    'unit' => $mealIngredient->unit,
                    'necessity_level' => $mealIngredient
                        ->necessity_level
                        ->value,
                    'substitution' => $mealIngredient->substituteIngredient
                        ? $mealIngredient->substituteIngredient->name
                        : "nan",
                ];
            }
            $recipes[] = [
                'recipe_id' => $meal->dataset_id,
                'recipe_name' => $meal->name,
                'servings' => $meal->servings,
                'prep_time' => $meal->prep_time->value,
                'difficulty_level' => $meal->difficulty_level->value,
                'seasonality' => $meal->seasonality->value,
                'ingredients' => $ingredients,
            ];
        }

        File::put(
            base_path('cpp/recipes.json'),
            json_encode(
                $recipes,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            )
        );
    }

    public function exportAll()
    {
        DB::transaction(function () {
            $this->exportIngredientsJson();
            $this->exportRecipesJson();
        });
    }
}