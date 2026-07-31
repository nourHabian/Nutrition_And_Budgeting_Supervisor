<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FamilySetupRequest extends FormRequest
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
            'family_members' => ['required', 'integer', 'min:1'],
            'always_available_ingredients' => ['present', 'array'],
            'always_available_ingredients.*' => ['string', 'exists:ingredients,name'],
            'allergic_ingredients' => ['present', 'array'],
            'allergic_ingredients.*' => ['string', 'exists:ingredients,name'],
            'disliked_ingredients' => ['present', 'array'],
            'disliked_ingredients.*' => ['string', 'exists:ingredients,name'],
            'favorite_meals' => ['present', 'array'],
            'favorite_meals.*' => ['string', 'exists:meals,name'],
            'disliked_meals' => ['present','array'],
            'disliked_meals.*' => ['string', 'exists:meals,name'],
        ];
    }

    public function messages(): array
    {
        return [
            // family_members
            'family_members.required' => 'عدد أفراد العائلة مطلوب',
            'family_members.integer' => 'عدد أفراد العائلة يجب أن يكون رقماً صحيحاً',
            'family_members.min' => 'عدد أفراد العائلة يجب أن يكون فرداً واحداً على الأقل',

            // always_available_ingredients
            'always_available_ingredients.present' => 'حقل المكونات المتوفرة دائماً مطلوب',
            'always_available_ingredients.array' => 'المكونات المتوفرة دائماً يجب أن تكون قائمة',

            'always_available_ingredients.*.string' => 'اسم المكون يجب أن يكون نصاً',
            'always_available_ingredients.*.exists' => 'أحد المكونات المختارة غير موجود',

            // allergic_ingredients
            'allergic_ingredients.present' => 'حقل المكونات التي تسبب الحساسية مطلوب',
            'allergic_ingredients.array' => 'المكونات التي تسبب الحساسية يجب أن تكون قائمة',

            'allergic_ingredients.*.string' => 'اسم المكون يجب أن يكون نصاً',
            'allergic_ingredients.*.exists' => 'أحد مكونات الحساسية المختارة غير موجود',

            // disliked_ingredients
            'disliked_ingredients.present' => 'حقل المكونات غير المرغوبة مطلوب',
            'disliked_ingredients.array' => 'المكونات غير المرغوبة يجب أن تكون قائمة',

            'disliked_ingredients.*.string' => 'اسم المكون يجب أن يكون نصاً',
            'disliked_ingredients.*.exists' => 'أحد المكونات غير المرغوبة غير موجود',

            // favorite_meals
            'favorite_meals.present' => 'حقل الوجبات المفضلة مطلوب',
            'favorite_meals.array' => 'الوجبات المفضلة يجب أن تكون قائمة',

            'favorite_meals.*.string' => 'اسم الوجبة يجب أن يكون نصاً',
            'favorite_meals.*.exists' => 'إحدى الوجبات المفضلة غير موجودة',

            // disliked_meals
            'disliked_meals.present' => 'حقل الوجبات غير المرغوبة مطلوب',
            'disliked_meals.array' => 'الوجبات غير المرغوبة يجب أن تكون قائمة',

            'disliked_meals.*.string' => 'اسم الوجبة يجب أن يكون نصاً',
            'disliked_meals.*.exists' => 'إحدى الوجبات غير المرغوبة غير موجودة',
        ];
    }
}
