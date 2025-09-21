<?php

namespace App\Http\Requests;

use App\Models\StockBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StockBatchRequest extends FormRequest
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
        $pharmacyId = auth()->user()->pharmacyProfile?->id;
        $stockBatchId = $this->route('stock_batch');

        return [
            'medicine_id' => [
                'required',
                'integer',
                'exists:medicines,id',
            ],
            'batch_num' => [
                'required',
                'string',
                'max:100',
                Rule::unique('stock_batches', 'batch_num')
                    ->where('pharmacy_id', $pharmacyId)
                    ->where('medicine_id', $this->medicine_id)
                    ->ignore($stockBatchId),
            ],
            'exp_date' => [
                'required',
                'date',
                'after:today',
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'medicine_id.required' => 'Please select a medicine.',
            'medicine_id.exists' => 'The selected medicine does not exist.',
            'batch_num.required' => 'Please provide a batch number.',
            'batch_num.unique' => 'This batch number already exists for this medicine in your pharmacy.',
            'exp_date.required' => 'Please provide the expiry date.',
            'exp_date.after' => 'Expiry date must be in the future.',
            'quantity.required' => 'Please specify the quantity.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.max' => 'Quantity cannot exceed 10,000.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'medicine_id' => 'medicine',
            'batch_num' => 'batch number',
            'exp_date' => 'expiry date',
            'quantity' => 'quantity',
        ];
    }
}
