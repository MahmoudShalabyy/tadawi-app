<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => 'required|in:cash,paypal',
            'billing_address' => 'required|string|max:500',
            'shipping_address' => 'nullable|string|max:500',
            'phone' => 'required|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'prescription_required' => 'required|boolean',
            'prescription_files' => 'required_if:prescription_required,true|array|min:1|max:3',
            'prescription_files.*' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
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
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Payment method must be either cash or PayPal',
            'billing_address.required' => 'Billing address is required',
            'billing_address.string' => 'Billing address must be a string',
            'billing_address.max' => 'Billing address cannot exceed 500 characters',
            'shipping_address.string' => 'Shipping address must be a string',
            'shipping_address.max' => 'Shipping address cannot exceed 500 characters',
            'phone.required' => 'Phone number is required',
            'phone.string' => 'Phone number must be a string',
            'phone.max' => 'Phone number cannot exceed 20 characters',
            'notes.string' => 'Notes must be a string',
            'notes.max' => 'Notes cannot exceed 1000 characters',
            'prescription_files.required_if' => 'Prescription files are required when prescription is needed',
            'prescription_files.max' => 'Maximum 3 prescription files allowed',
            'prescription_files.*.mimes' => 'Prescription files must be JPG, JPEG, PNG, PDF, DOC, or DOCX',
            'prescription_files.*.max' => 'Each prescription file cannot exceed 5MB',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert string boolean to actual boolean
        if ($this->has('prescription_required')) {
            $this->merge([
                'prescription_required' => filter_var($this->prescription_required, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'payment_method' => 'payment method',
            'billing_address' => 'billing address',
            'shipping_address' => 'shipping address',
            'phone' => 'phone number',
            'notes' => 'notes',
            'prescription_required' => 'prescription required',
            'prescription_files' => 'prescription files',
        ];
    }

}
