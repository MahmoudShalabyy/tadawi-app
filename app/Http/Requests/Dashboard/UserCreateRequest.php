<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class UserCreateRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'phone_number' => 'required|unique:users|digits:11',
            'profile_picture_path' => 'nullable|image|max:2048',
            'role' => 'required|in:patient,pharmacy,admin,doctor',
            'status' => 'required|in:pending,active,suspended',
            'travel_mode' => 'required|boolean',
            'google_id' => 'nullable|string',
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
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'The email has already been taken.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'phone_number.required' => 'The phone number field is required.',
            'phone_number.unique' => 'The phone number has already been taken.',
            'phone_number.digits' => 'The phone number must be exactly 11 digits.',
            'profile_picture_path.image' => 'The profile picture must be an image.',
            'profile_picture_path.max' => 'The profile picture may not be greater than 2048 kilobytes.',
            'role.required' => 'The role field is required.',
            'role.in' => 'The selected role is invalid.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The selected status is invalid.',
            'travel_mode.required' => 'The travel mode field is required.',
            'travel_mode.boolean' => 'The travel mode must be true or false.',
            'google_id.string' => 'The Google ID must be a string.',
        ];
    }
}
