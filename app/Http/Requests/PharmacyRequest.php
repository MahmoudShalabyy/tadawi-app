<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PharmacyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $pharmacyId = $this->route('pharmacy');

        return [
            'location' => [
                'required',
                'string',
                'max:255',
            ],
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
            'contact_info' => [
                'required',
                'string',
                'max:255',
            ],
            'verified' => [
                'sometimes',
                'boolean',
            ],
            'rating' => [
                'sometimes',
                'numeric',
                'between:0,5',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'location.required' => 'Please provide the pharmacy location.',
            'location.max' => 'Location cannot exceed 255 characters.',
            'latitude.required' => 'Please provide the latitude coordinate.',
            'latitude.numeric' => 'Latitude must be a valid number.',
            'latitude.between' => 'Latitude must be between -90 and 90 degrees.',
            'longitude.required' => 'Please provide the longitude coordinate.',
            'longitude.numeric' => 'Longitude must be a valid number.',
            'longitude.between' => 'Longitude must be between -180 and 180 degrees.',
            'contact_info.required' => 'Please provide contact information.',
            'contact_info.max' => 'Contact information cannot exceed 255 characters.',
            'verified.boolean' => 'Verified status must be true or false.',
            'rating.numeric' => 'Rating must be a valid number.',
            'rating.between' => 'Rating must be between 0 and 5.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'location' => 'location',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'contact_info' => 'contact information',
            'verified' => 'verified status',
            'rating' => 'rating',
        ];
    }
}
