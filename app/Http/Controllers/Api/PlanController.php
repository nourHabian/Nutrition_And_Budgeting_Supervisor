<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GeneratePlanRequest;
use App\Http\Requests\PlanAcceptRequest;
use App\Services\PlanAcceptService;
use App\Services\PlanGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanController extends Controller
{
    public function generate(GeneratePlanRequest $request, PlanGenerationService $service)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $result = $service->generate(
            $user,
            $request->validated()
        );
        
        if ($result === null) {
            return response()->json([
                'message' => 'لم يتم العثور على خطة مناسبة بالميزانية والمعطيات المدخلة، جرّب زيادة الميزانية أو تعديل المكونات أو التفضيلات.'
            ], 422);
        }

        return response()->json($result);
    }

    public function accept(PlanAcceptRequest $request, PlanAcceptService $service)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $plan = $service->accept(
            $user,
            $request->validated()
        );

        $plan->load([
            'shoppingListItems.ingredient'
        ]);

        return response()->json([
            'message' => 'تم قبول الخطة بنجاح.',
            'plan_id' => $plan->id,
            'shopping_list' => $plan->shoppingListItems->map(function ($item) {
                return [
                    'ingredient' => $item->ingredient->name,
                    'required_quantity' => $item->required_quantity,
                    'available_quantity' => $item->available_quantity,
                    'unit' => $item->unit,
                    'estimated_price' => $item->estimated_price,
                ];
            }),
        ]);
    }
}
