<?php

namespace App\Http\Resources;

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
        $latestPayment = $this->payments->first();
        
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_display' => $this->getStatusDisplayAttribute(),
            'payment_method' => $this->payment_method,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'total_items' => $this->total_items,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Pharmacy information
            'pharmacy' => $this->whenLoaded('pharmacy', function () {
                return [
                    'id' => $this->pharmacy->id,
                    'location' => $this->pharmacy->location,
                    'contact_info' => $this->pharmacy->contact_info,
                    'verified' => $this->pharmacy->verified,
                ];
            }),
            
            // Payment information
            'payment' => $latestPayment ? [
                'id' => $latestPayment->id,
                'status' => $latestPayment->status,
                'method' => $latestPayment->method,
                'amount' => $latestPayment->amount,
                'currency' => $latestPayment->currency,
                'transaction_id' => $latestPayment->transaction_id,
                'created_at' => $latestPayment->created_at,
            ] : null,
            
            // Order items
            'items' => $this->whenLoaded('medicines', function () {
                return $this->medicines->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'medicine_id' => $item->medicine_id,
                        'medicine_name' => $item->medicine->brand_name ?? 'Unknown Medicine',
                        'medicine_form' => $item->medicine->form ?? null,
                        'medicine_dosage' => $item->medicine->dosage_strength ?? null,
                        'quantity' => $item->quantity,
                        'price_at_time' => $item->price_at_time,
                        'line_total' => $item->price_at_time * $item->quantity,
                    ];
                });
            }),
            
            // Prescription uploads (if any)
            'prescription_uploads' => $this->whenLoaded('prescriptionUploads', function () {
                return $this->prescriptionUploads->map(function ($upload) {
                    return [
                        'id' => $upload->id,
                        'file_path' => $upload->file_path,
                        'created_at' => $upload->created_at,
                    ];
                });
            }),
        ];
    }
}
