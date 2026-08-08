<?php

namespace App\Http\Requests;

use App\Enums\PrepTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanAcceptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'budget' => ['required', 'integer', 'min:0'],
            'servings' => ['required', 'integer', 'min:1'],
            'number_of_meals' => ['required', 'integer', 'between:1,7'],
            'days_per_meal' => ['required', 'integer', Rule::in([1, 2])],
            'prep_time' => ['required', Rule::enum(PrepTime::class)],
            'available_ingredients' => ['sometimes', 'array'],
            'available_ingredients.*.name' => ['required', 'string', 'exists:ingredients,name'],
            'available_ingredients.*.quantity' => ['required', 'integer', 'min:1'],
            'total_cost' => ['required','integer','min:0'],
            'meals' => ['required','array','min:1'],
            'meals.*.meal_id' => ['required', 'exists:meals,dataset_id'],
            'meals.*.expanded_meal_id' => ['required', 'integer'],
            'meals.*.estimated_cost' => ['required', 'integer'],
            'meals.*.ingredients' => ['required', 'array'],
            'meals.*.ingredients.*.id' => ['required', 'exists:ingredients,dataset_id'],
            'meals.*.ingredients.*.quantity' => ['required', 'integer'],
            'meals.*.ingredients.*.unit' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [

            'budget.required' => 'يجب إدخال الميزانية.',
            'budget.integer' => 'يجب أن تكون الميزانية رقماً صحيحاً.',
            'budget.min' => 'لا يمكن أن تكون الميزانية أقل من صفر.',

            'servings.required' => 'يجب إدخال عدد الأشخاص.',
            'servings.integer' => 'عدد الأشخاص يجب أن يكون رقماً صحيحاً.',
            'servings.min' => 'يجب أن يكون عدد الأشخاص شخصاً واحداً على الأقل.',

            'number_of_meals.required' => 'يجب إدخال عدد الوجبات.',
            'number_of_meals.integer' => 'عدد الوجبات يجب أن يكون رقماً صحيحاً.',
            'number_of_meals.between' => 'عدد الوجبات يجب أن يكون بين 1 و7.',

            'days_per_meal.required' => 'يجب تحديد مدة الخطة.',
            'days_per_meal.integer' => 'مدة الخطة غير صحيحة.',
            'days_per_meal.in' => 'مدة الخطة يجب أن تكون يوماً واحداً أو يومين.',

            'prep_time.required' => 'يجب تحديد وقت التحضير.',
            'prep_time.enum' => 'وقت التحضير المحدد غير صحيح.',

            'available_ingredients.required' => 'يجب إدخال قائمة المكونات المتوفرة.',
            'available_ingredients.array' => 'صيغة المكونات المتوفرة غير صحيحة.',

            'available_ingredients.*.name.required' => 'اسم المكون مطلوب.',
            'available_ingredients.*.name.string' => 'اسم المكون غير صالح.',
            'available_ingredients.*.name.exists' => 'أحد المكونات غير موجود ضمن قاعدة البيانات.',

            'available_ingredients.*.quantity.required' => 'كمية المكون مطلوبة.',
            'available_ingredients.*.quantity.integer' => 'كمية المكون يجب أن تكون رقماً صحيحاً.',
            'available_ingredients.*.quantity.min' => 'كمية المكون يجب أن تكون أكبر من صفر.',

            'total_cost.required' => 'التكلفة الإجمالية مطلوبة.',
            'total_cost.integer' => 'التكلفة الإجمالية يجب أن تكون رقماً صحيحاً.',
            'total_cost.min' => 'التكلفة الإجمالية لا يمكن أن تكون سالبة.',

            'meals.required' => 'يجب إرسال الوجبات.',
            'meals.array' => 'صيغة الوجبات غير صحيحة.',
            'meals.min' => 'يجب أن تحتوي الخطة على وجبة واحدة على الأقل.',

            'meals.*.meal_id.required' => 'معرف الوجبة مطلوب.',
            'meals.*.meal_id.exists' => 'إحدى الوجبات غير موجودة.',

            'meals.*.expanded_meal_id.required' => 'معرف التشكيلة مطلوب.',
            'meals.*.expanded_meal_id.integer' => 'معرف التشكيلة غير صالح.',

            'meals.*.estimated_cost.required' => 'تكلفة الوجبة مطلوبة.',
            'meals.*.estimated_cost.integer' => 'تكلفة الوجبة يجب أن تكون رقماً صحيحاً.',

            'meals.*.ingredients.required' => 'يجب إرسال مكونات الوجبة.',
            'meals.*.ingredients.array' => 'صيغة مكونات الوجبة غير صحيحة.',

            'meals.*.ingredients.*.id.required' => 'معرف المكون مطلوب.',
            'meals.*.ingredients.*.id.exists' => 'أحد المكونات غير موجود.',

            'meals.*.ingredients.*.quantity.required' => 'كمية المكون مطلوبة.',
            'meals.*.ingredients.*.quantity.integer' => 'كمية المكون يجب أن تكون رقماً صحيحاً.',

            'meals.*.ingredients.*.unit.required' => 'وحدة القياس مطلوبة.',
            'meals.*.ingredients.*.unit.string' => 'وحدة القياس غير صحيحة.',
        ];
    }
}
