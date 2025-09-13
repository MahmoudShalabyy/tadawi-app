<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_name' => $this->brand_name,
            'form' => $this->form,
            'dosage_strength' => $this->dosage_strength,
            'manufacturer' => $this->manufacturer,
            'price' => $this->price,
            'total_quantity' => $this->whenLoaded('stockBatches', function () {
                return $this->stockBatches->sum('quantity');
            }),
            'active_ingredient' => $this->whenLoaded('activeIngredient', function () {
                return [
                    'id' => $this->activeIngredient->id,
                    'name' => $this->activeIngredient->name,
                ];
            }),
            'therapeutic_classes' => $this->whenLoaded('therapeuticClasses', function () {
                return $this->therapeuticClasses->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'note' => $class->pivot->note ?? null,
                    ];
                });
            }),
            'stock_batches' => $this->whenLoaded('stockBatches', function () {
                return $this->stockBatches->map(function ($batch) {
                    return [
                        'id' => $batch->id,
                        'batch_num' => $batch->batch_num,
                        'exp_date' => $batch->exp_date,
                        'quantity' => $batch->quantity,
                        'pharmacy_id' => $batch->pharmacy_id,
                    ];
                });
            }),
        ];
    }
}
