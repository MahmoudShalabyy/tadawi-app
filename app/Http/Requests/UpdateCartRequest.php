<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'quantity' => 'required|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'quantity.min' => 'Quantity must be greater than 0',
        ];
    }
}