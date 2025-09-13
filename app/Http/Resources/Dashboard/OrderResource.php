<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'pharmacy_id' => $this->pharmacy_id,
            'patient_name' => $this->patient_name ?? $this->user->name ?? null,
            'pharmacy_location' => $this->pharmacy_location ?? $this->pharmacy->location ?? null,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'billing_address' => $this->billing_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
            'pharmacy' => $this->whenLoaded('pharmacy', function () {
                return [
                    'id' => $this->pharmacy->id,
                    'location' => $this->pharmacy->location,
                    'contact_info' => $this->pharmacy->contact_info,
                    'verified' => $this->pharmacy->verified,
                ];
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
                    ];
                });
            }),
            'prescription_uploads' => $this->whenLoaded('prescriptionUploads', function () {
                return PrescriptionResource::collection($this->prescriptionUploads);
            }),
        ];
    }
}
