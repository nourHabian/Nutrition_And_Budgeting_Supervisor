<?php

namespace App\Http\Requests;

use App\Enums\PrepTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budget' => ['required', 'integer', 'min:0'],
            'servings' => ['required', 'integer', 'min:1'],
            'number_of_meals' => ['required', 'integer', 'between:1,7'],
            'days_per_meal' => ['required', 'integer', Rule::in([1, 2])],
            'prep_time' => ['required', Rule::enum(PrepTime::class)],
            'available_ingredients' => ['required', 'array'],
            'available_ingredients.*.name' => ['required', 'string', 'exists:ingredients,name'],
            'available_ingredients.*.quantity' => ['required', 'integer', 'min:1'],
            'required_meals' => ['sometimes', 'array'],
            'required_meals.*.meal_id' => ['required', 'exists:meals,dataset_id'],
            'required_meals.*.expanded_meal_id' => ['required', 'integer'],
            'excluded_meals' => ['sometimes', 'array'],
            'excluded_meals.*' => ['integer', 'exists:meals,dataset_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'budget.required' => 'يجب إدخال الميزانية.',
            'budget.integer' => 'الميزانية يجب أن تكون رقماً صحيحاً.',
            'budget.min' => 'الميزانية لا يمكن أن تكون سالبة.',

            'servings.required' => 'يجب إدخال عدد الأشخاص.',
            'servings.integer' => 'عدد الأشخاص يجب أن يكون رقماً صحيحاً.',
            'servings.min' => 'عدد الأشخاص يجب أن يكون شخصاً واحداً على الأقل.',

            'number_of_meals.required' => 'يجب إدخال عدد الوجبات.',
            'number_of_meals.between' => 'عدد الوجبات يجب أن يكون بين 1 و7.',

            'days_per_meal.required' => 'يجب تحديد مدة الوجبة.',
            'days_per_meal.in' => 'مدة الوجبة يجب أن تكون يوماً واحداً أو يومين.',

            'prep_time.required' => 'يجب اختيار وقت التحضير.',
            'prep_time.enum' => 'وقت التحضير غير صحيح.',

            'available_ingredients.required' => 'يجب إدخال المكونات المتوفرة.',
            'available_ingredients.array' => 'صيغة المكونات غير صحيحة.',

            'available_ingredients.*.name.required' => 'اسم المكون مطلوب.',
            'available_ingredients.*.name.exists' => 'أحد المكونات غير موجود في قاعدة البيانات.',

            'available_ingredients.*.quantity.required' => 'كمية المكون مطلوبة.',
            'available_ingredients.*.quantity.integer' => 'الكمية يجب أن تكون رقماً صحيحاً.',
            'available_ingredients.*.quantity.min' => 'الكمية يجب أن تكون أكبر من صفر.',

            'required_meals.array' => 'الوجبات المطلوبة يجب أن تكون مصفوفة.',
            'required_meals.*.meal_id.exists' => 'إحدى الوجبات المطلوبة غير موجودة.',
            'required_meals.*.expanded_meal_id.required' => 'يجب إرسال معرف التشكيلة للوجبة المطلوبة.',

            'excluded_meals.array' => 'الوجبات المستبعدة يجب أن تكون مصفوفة.',
            'excluded_meals.*.exists' => 'إحدى الوجبات المستبعدة غير موجودة.',
        ];
    }
}