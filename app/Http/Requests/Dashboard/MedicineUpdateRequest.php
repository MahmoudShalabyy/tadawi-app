<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class MedicineUpdateRequest extends FormRequest
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
            'brand_name' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:100',
            'dosage_strength' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string',
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'active_ingredient_id' => 'nullable|exists:active_ingredients,id',
            'pharmacy_id' => 'nullable|exists:pharmacy_profiles,user_id',
            'quantity' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'brand_name.string' => 'The brand name must be a string.',
            'brand_name.max' => 'The brand name may not be greater than 255 characters.',
            'form.string' => 'The form must be a string.',
            'form.max' => 'The form may not be greater than 100 characters.',
            'dosage_strength.string' => 'The dosage strength must be a string.',
            'dosage_strength.max' => 'The dosage strength may not be greater than 100 characters.',
            'manufacturer.string' => 'The manufacturer must be a string.',
            'price.numeric' => 'The price must be a number.',
            'price.min' => 'The price must be at least 0.',
            'price.max' => 'The price may not be greater than 999999.99.',
            'active_ingredient_id.exists' => 'The selected active ingredient does not exist.',
            'pharmacy_id.exists' => 'The selected pharmacy does not exist.',
            'quantity.integer' => 'The quantity must be an integer.',
            'quantity.min' => 'The quantity must be at least 0.',
        ];
    }
}
