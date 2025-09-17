<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'medicine_id' => 'required|integer|exists:medicines,id',
            'pharmacy_id' => 'required|integer|exists:pharmacy_profiles,id',
            'quantity' => 'required|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'quantity.min' => 'Quantity must be greater than 0',
            'medicine_id.integer' => 'Medicine ID must be a valid number',
            'pharmacy_id.integer' => 'Pharmacy ID must be a valid number',
        ];
    }

    // إضافة custom validation للـstock (اختياري)
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $stock = \App\Models\StockBatch::where('pharmacy_id', $this->pharmacy_id)
                ->where('medicine_id', $this->medicine_id)
                ->first();

            if ($stock && $stock->quantity < $this->quantity) {
                $validator->errors()->add('quantity', "Only {$stock->quantity} available");
            }
        });
    }
}