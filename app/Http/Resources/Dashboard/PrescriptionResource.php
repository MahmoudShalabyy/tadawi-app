<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
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
            'order_id' => $this->order_id,
            'user_id' => $this->user_id ?? $this->order->user_id ?? null,
            'patient_name' => $this->patient_name ?? $this->order->user->name ?? null,
            'file_path' => $this->file_path,
            'ocr_text' => $this->ocr_text,
            'validated_by_doctor' => $this->validated_by_doctor,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order->id,
                    'status' => $this->order->status,
                    'user_id' => $this->order->user_id,
                ];
            }),
        ];
    }
}
