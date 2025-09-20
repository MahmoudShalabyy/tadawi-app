<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderMedicine;
use App\Models\StockBatch;
use App\Models\Medicine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartService
{
    /**
     * Get cart configuration
     */
    public function getConfig(): array
    {
        return [
            'max_quantity_per_medicine' => config('cart.max_quantity_per_medicine', 2),
            'session_expiry_hours' => config('cart.session_expiry_hours', 24),
        ];
    }

    /**
     * Validate medicine quantity limit
     */
    public function validateQuantityLimit(int $quantity, ?OrderMedicine $existing = null): array
    {
        $config = $this->getConfig();
        $maxPerMedicine = $config['max_quantity_per_medicine'];
        
        $newQuantity = $existing ? $existing->quantity + $quantity : $quantity;
        
        if ($newQuantity > $maxPerMedicine) {
            return [
                'valid' => false,
                'message' => "Cannot add more than {$maxPerMedicine} of the same medicine. Requested: {$newQuantity}, Max allowed: {$maxPerMedicine}",
                'current_quantity' => $existing ? $existing->quantity : 0,
                'requested_quantity' => $quantity,
                'max_per_medicine' => $maxPerMedicine,
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate price consistency
     */
    public function validatePriceConsistency(float $currentPrice, ?OrderMedicine $existing = null): array
    {
        if ($existing && $existing->price_at_time != $currentPrice) {
            return [
                'valid' => false,
                'message' => 'Medicine price has changed. Please refresh and try again.',
                'old_price' => $existing->price_at_time,
                'new_price' => $currentPrice,
                'price_change' => $currentPrice - $existing->price_at_time,
            ];
        }

        return ['valid' => true];
    }

    /**
     * Calculate stock status based on requested quantity
     */
    public function calculateStockStatus(?StockBatch $stock, int $requestedQuantity): string
    {
        if (!$stock) {
            return 'unknown';
        }

        if ($stock->quantity == 0) {
            return 'out_of_stock';
        }

        if ($stock->quantity < $requestedQuantity) {
            return 'insufficient_stock';
        }

        if ($stock->quantity < 5) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Clean up expired carts
     */
    public function cleanupExpiredCarts(): int
    {
        if (!config('cart.auto_cleanup_enabled', true)) {
            return 0;
        }

        $expiryHours = $this->getConfig()['session_expiry_hours'];
        $expiredCarts = Order::where('status', 'cart')
            ->where('updated_at', '<', now()->subHours($expiryHours))
            ->get();

        $deletedCount = 0;
        foreach ($expiredCarts as $cart) {
            try {
                $cart->medicines()->delete();
                $cart->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                Log::error("Failed to delete expired cart {$cart->id}: " . $e->getMessage());
            }
        }

        if ($deletedCount > 0) {
            Log::info("Cleaned up {$deletedCount} expired carts");
        }

        return $deletedCount;
    }

    /**
     * Check if cart is expired
     */
    public function isCartExpired(Order $cart): bool
    {
        $expiryHours = $this->getConfig()['session_expiry_hours'];
        return $cart->updated_at < now()->subHours($expiryHours);
    }
}
