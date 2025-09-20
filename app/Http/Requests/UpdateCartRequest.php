<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
    public function authorize() 
    { 
        // Require authenticated user
        return auth()->check();
    }

    public function rules()
    {
        return [
            'quantity' => 'required|integer|min:1|max:2',
        ];
    }

    public function messages()
    {
        return [
            'quantity.min' => 'Quantity must be greater than 0',
            'quantity.max' => 'Cannot have more than 2 of the same medicine',
        ];
    }
}