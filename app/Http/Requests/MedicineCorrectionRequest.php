<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MedicineCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authentication requirements
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'medicine_name' => [
                'required',
                'string',
                'min:1',
                'max:200',
                'regex:/^[a-zA-Z0-9\s\-\.\x{0600}-\x{06FF}]+$/u' // Allow English, Arabic, numbers, spaces, hyphens, dots
            ]
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
            'medicine_name.required' => 'Medicine name is required.',
            'medicine_name.string' => 'Medicine name must be a string.',
            'medicine_name.min' => 'Medicine name must be at least 1 character long.',
            'medicine_name.max' => 'Medicine name must not exceed 200 characters.',
            'medicine_name.regex' => 'Medicine name contains invalid characters. Only letters, numbers, spaces, hyphens, and dots are allowed.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'medicine_name' => 'medicine name'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace and normalize the input
        if ($this->has('medicine_name')) {
            $this->merge([
                'medicine_name' => trim($this->input('medicine_name'))
            ]);
        }
    }
}
