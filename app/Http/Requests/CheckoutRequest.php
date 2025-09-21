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
            'prescription_required' => 'boolean',
            'prescription_files' => 'nullable|array|max:5',
            'prescription_files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max per file
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
            'prescription_required.boolean' => 'Prescription required must be true or false',
            'prescription_files.array' => 'Prescription files must be an array',
            'prescription_files.max' => 'Maximum 5 prescription files allowed',
            'prescription_files.*.file' => 'Each prescription file must be a valid file',
            'prescription_files.*.mimes' => 'Prescription files must be JPG, JPEG, PNG, or PDF',
            'prescription_files.*.max' => 'Each prescription file cannot exceed 5MB',
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
