<?php

namespace App\Http\Resources;

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
            'status' => $this->status,
            'contact_info' => $this->contact_info,
            'sealed_confirmed' => $this->sealed_confirmed,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // User information (only include if user relationship is loaded)
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone_number' => $this->user->phone_number,
                ];
            }),
            
            // Medicine information
            'medicines' => $this->whenLoaded('medicines', function () {
                return $this->medicines->map(function ($medicine) {
                    return [
                        'id' => $medicine->id,
                        'name' => $medicine->name,
                        'brand_name' => $medicine->brand_name,
                        'strength' => $medicine->strength,
                        'form' => $medicine->form,
                        'active_ingredients' => $medicine->active_ingredients->pluck('name'),
                        'therapeutic_class' => $medicine->therapeuticClass->name ?? null,
                        'image_url' => $medicine->image_url,
                        'quantity' => $medicine->pivot->quantity,
                        'expiry_date' => $medicine->pivot->expiry_date,
                        'batch_number' => $medicine->pivot->batch_num,
                    ];
                });
            }),
            
            // Photos information
            'photos' => $this->whenLoaded('photos', function () {
                return $this->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'photo_url' => asset('storage/donations/' . $photo->photo_path),
                        'created_at' => $photo->created_at,
                    ];
                });
            }),
            
            // Status information
            'status_info' => [
                'current' => $this->status,
                'can_update' => $this->status === 'proposed',
                'can_cancel' => $this->status === 'proposed',
                'is_available' => $this->status === 'approved',
            ],
        ];
    }
}
