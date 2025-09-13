<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class SiteConfigRequest extends FormRequest
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
            'site_name' => 'required|string|max:255',
            'email_config' => 'required|email',
            'map_api_key' => 'required|string',
            'ocr_api_key' => 'required|string',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'site_theme' => 'required|in:light,dark',
            'timezone' => 'required|string|in:' . implode(',', timezone_identifiers_list()),
            'currency' => 'required|in:EGP,USD',
            'public_key' => 'required|string',
            'private_key' => 'required|string',
            'ai_link' => 'required|url',
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
            'site_name.required' => 'The site name is required.',
            'site_name.string' => 'The site name must be a string.',
            'site_name.max' => 'The site name may not be greater than 255 characters.',
            'email_config.required' => 'The email configuration is required.',
            'email_config.email' => 'The email configuration must be a valid email address.',
            'map_api_key.required' => 'The map API key is required.',
            'map_api_key.string' => 'The map API key must be a string.',
            'ocr_api_key.required' => 'The OCR API key is required.',
            'ocr_api_key.string' => 'The OCR API key must be a string.',
            'site_logo.image' => 'The site logo must be an image.',
            'site_logo.mimes' => 'The site logo must be a file of type: jpeg, png, jpg, gif.',
            'site_logo.max' => 'The site logo may not be greater than 2048 kilobytes.',
            'site_theme.required' => 'The site theme is required.',
            'site_theme.in' => 'The site theme must be either light or dark.',
            'timezone.required' => 'The timezone is required.',
            'timezone.in' => 'The timezone must be a valid PHP timezone.',
            'currency.required' => 'The currency is required.',
            'currency.in' => 'The currency must be either EGP or USD.',
            'public_key.required' => 'The public key is required.',
            'public_key.string' => 'The public key must be a string.',
            'private_key.required' => 'The private key is required.',
            'private_key.string' => 'The private key must be a string.',
            'ai_link.required' => 'The AI link is required.',
            'ai_link.url' => 'The AI link must be a valid URL.',
        ];
    }
}
