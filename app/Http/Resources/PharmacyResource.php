<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacyResource extends JsonResource
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
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'contact_info' => $this->contact_info,
            'verified' => $this->verified,
            'rating' => $this->rating,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Include user information if loaded
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),

            // Include relationships if loaded
            'stock_batches_count' => $this->whenLoaded('stockBatches', function () {
                return $this->stockBatches->count();
            }),

            'orders_count' => $this->whenLoaded('orders', function () {
                return $this->orders->count();
            }),

            'reviews_count' => $this->whenLoaded('reviews', function () {
                return $this->reviews->count();
            }),
        ];
    }
}
