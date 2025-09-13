<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
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
        $userId = $this->route('user'); // Get the user ID from the route parameter

        return [
            'name' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'role' => 'nullable|in:patient,pharmacy,admin,doctor',
            'status' => 'nullable|in:pending,active,suspended',
            'travel_mode' => 'nullable|boolean',
            'phone_number' => [
                'nullable',
                'digits:11',
                Rule::unique('users', 'phone_number')->ignore($userId)
            ],
            'profile_picture_path' => 'nullable|image|max:2048',
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
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'The email has already been taken.',
            'role.in' => 'The selected role is invalid.',
            'status.in' => 'The selected status is invalid.',
            'travel_mode.boolean' => 'The travel mode must be true or false.',
            'phone_number.unique' => 'The phone number has already been taken.',
            'phone_number.digits' => 'The phone number must be exactly 11 digits.',
            'profile_picture_path.image' => 'The profile picture must be an image.',
            'profile_picture_path.max' => 'The profile picture may not be greater than 2048 kilobytes.',
            'google_id.string' => 'The Google ID must be a string.',
        ];
    }
}
