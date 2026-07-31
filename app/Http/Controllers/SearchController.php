<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Meal;
use Illuminate\Http\Request;

class SearchController extends Controller
{

    public function ingredients(Request $request)
    {
        $query = $request->query('query');
        if (!$query) {
            return response()->json(['message' => 'يرجى إدخال كلمة للبحث'], 400);
        }
        
        $ingredients = Ingredient::where('name', 'like', "%{$query}%")->select([
                'id',
                'name',
                'protein',
                'carbohydrates',
                'fiber',
                'price'
            ])->limit(20)->get();

        return response()->json([
            'data' => $ingredients
        ]);
    }

    public function meals(Request $request)
    {
        $query = $request->query('query');
        if (!$query) {
            return response()->json(['message' => 'يرجى إدخال كلمة للبحث'], 400);
        }

        $meals = Meal::where('name', 'like', "%{$query}%")->select([
                'id',
                'name',
                'servings',
                'prep_time',
                'difficulty_level',
                'seasonality'
            ])->limit(20)->get();

        return response()->json([
            'data'=>$meals
        ]);
    }

}