<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $method = $this->method();
        $route = $this->route()->getName();

        // Patient profile validation
        if ($method === 'POST' && $this->is('api/v1/profiles/patient')) {
            return [
                'date_of_birth' => [
                    'required',
                    'date',
                    'before:' . now()->subYears(14)->format('Y-m-d')
                ],
                'gender' => [
                    'required',
                    Rule::in(['male', 'female'])
                ],
                'national_id' => [
                    'required',
                    'string',
                    'regex:/^[0-9]{14}$/',
                    'unique:patient_profiles,national_id'
                ],
                'medical_history_summary' => [
                    'required',
                    'string',
                    'max:1000'
                ],
                'default_address' => [
                    'required',
                    'string',
                    'max:500'
                ]
            ];
        }

        // Doctor profile validation
        if ($method === 'POST' && $this->is('api/v1/profiles/doctor')) {
            return [
                'medical_license_id' => [
                    'required',
                    'string',
                    'max:255',
                    'unique:doctor_profiles,medical_license_id'
                ],
                'specialization' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'clinic_address' => [
                    'required',
                    'string',
                    'max:500'
                ]
            ];
        }

        // Pharmacy profile validation
        if ($method === 'POST' && $this->is('api/v1/profiles/pharmacy')) {
            return [
                'location' => [
                    'required',
                    'string',
                    'max:500'
                ],
                'latitude' => [
                    'nullable',
                    'numeric',
                    'between:-90,90'
                ],
                'longitude' => [
                    'nullable',
                    'numeric',
                    'between:-180,180'
                ],
                'contact_info' => [
                    'required',
                    'string',
                    'regex:/^\+20[0-9]{10}$/'
                ]
            ];
        }

        return [];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            // Patient profile messages
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.date' => 'Please provide a valid date.',
            'date_of_birth.before' => 'You must be at least 14 years old.',
            
            'gender.required' => 'Gender is required.',
            'gender.in' => 'Gender must be either male or female.',
            
            'national_id.required' => 'National ID is required.',
            'national_id.regex' => 'National ID must be exactly 14 digits.',
            'national_id.unique' => 'This national ID is already registered.',
            
            'medical_history_summary.required' => 'Medical history summary is required.',
            'medical_history_summary.max' => 'Medical history summary cannot exceed 1000 characters.',
            
            'default_address.required' => 'Default address is required.',
            'default_address.max' => 'Default address cannot exceed 500 characters.',

            // Doctor profile messages
            'medical_license_id.required' => 'Medical license ID is required.',
            'medical_license_id.max' => 'Medical license ID cannot exceed 255 characters.',
            'medical_license_id.unique' => 'This medical license ID is already registered.',
            
            'specialization.required' => 'Specialization is required.',
            'specialization.max' => 'Specialization cannot exceed 255 characters.',
            
            'clinic_address.required' => 'Clinic address is required.',
            'clinic_address.max' => 'Clinic address cannot exceed 500 characters.',

            // Pharmacy profile messages
            'location.required' => 'Location address is required.',
            'location.max' => 'Location address cannot exceed 500 characters.',
            
            'latitude.numeric' => 'Latitude must be a valid number.',
            'latitude.between' => 'Latitude must be between -90 and 90 degrees.',
            
            'longitude.numeric' => 'Longitude must be a valid number.',
            'longitude.between' => 'Longitude must be between -180 and 180 degrees.',
            
            'contact_info.required' => 'Contact information is required.',
            'contact_info.regex' => 'Contact information must be in Egyptian format: +20 followed by 10 digits.'
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'date_of_birth' => 'date of birth',
            'gender' => 'gender',
            'national_id' => 'national ID',
            'medical_history_summary' => 'medical history summary',
            'default_address' => 'default address',
            'medical_license_id' => 'medical license ID',
            'specialization' => 'specialization',
            'clinic_address' => 'clinic address',
            'location' => 'location address',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'contact_info' => 'contact information'
        ];
    }
}