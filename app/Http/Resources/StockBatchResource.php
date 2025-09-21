<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockBatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isExpired = $this->exp_date < now();
        $isExpiringSoon = $this->exp_date <= now()->addDays(30) && $this->exp_date > now();
        $isLowStock = $this->quantity <= 10;

        return [
            'id' => $this->id,
            'pharmacy_id' => $this->pharmacy_id,
            'medicine_id' => $this->medicine_id,
            'batch_num' => $this->batch_num,
            'exp_date' => $this->exp_date,
            'quantity' => $this->quantity,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Status indicators
            'is_expired' => $isExpired,
            'is_expiring_soon' => $isExpiringSoon,
            'is_low_stock' => $isLowStock,
            'days_until_expiry' => $isExpired ? 0 : now()->diffInDays($this->exp_date, false),

            // Include medicine information if loaded
            'medicine' => $this->whenLoaded('medicine', function () {
                return [
                    'id' => $this->medicine->id,
                    'brand_name' => $this->medicine->brand_name,
                    'form' => $this->medicine->form,
                    'dosage_strength' => $this->medicine->dosage_strength,
                    'manufacturer' => $this->medicine->manufacturer,
                    'price' => $this->medicine->price,
                ];
            }),

            // Include pharmacy information if loaded
            'pharmacy' => $this->whenLoaded('pharmacy', function () {
                return [
                    'id' => $this->pharmacy->id,
                    'location' => $this->pharmacy->location,
                    'verified' => $this->pharmacy->verified,
                    'rating' => $this->pharmacy->rating,
                ];
            }),
        ];
    }
}
