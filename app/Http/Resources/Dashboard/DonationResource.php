<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonationResource extends JsonResource
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
            'user_id' => $this->user_id,
            'donor_name' => $this->donor_name ?? $this->user->name ?? null,
            'location' => $this->location,
            'contact_info' => $this->contact_info,
            'verified' => $this->verified,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
            'medicines' => $this->whenLoaded('medicines', function () {
                return $this->medicines->map(function ($medicine) {
                    return [
                        'id' => $medicine->id,
                        'brand_name' => $medicine->brand_name,
                        'form' => $medicine->form,
                        'dosage_strength' => $medicine->dosage_strength,
                        'manufacturer' => $medicine->manufacturer,
                        'price' => $medicine->price,
                        'quantity' => $medicine->pivot->quantity,
                        'expiry_date' => $medicine->pivot->expiry_date,
                        'batch_num' => $medicine->pivot->batch_num,
                    ];
                });
            }),
        ];
    }
}
