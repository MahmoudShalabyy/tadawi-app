<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderMedicine;
use App\Models\StockBatch;
use App\Models\Medicine;
use App\Models\PharmacyProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckoutService
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Validate cart for checkout
     */
    public function validateCartForCheckout(int $cartId, int $userId): array
    {
        try {
            $cart = Order::where('id', $cartId)
                ->where('user_id', $userId)
                ->where('status', 'cart')
                ->with(['medicines.medicine', 'pharmacy'])
                ->first();

            if (!$cart) {
                return [
                    'valid' => false,
                    'message' => 'Cart not found or not accessible',
                    'errors' => ['cart_not_found']
                ];
            }

            // Check if cart is expired
            if ($this->cartService->isCartExpired($cart)) {
                return [
                    'valid' => false,
                    'message' => 'Cart has expired. Please add items again.',
                    'errors' => ['cart_expired']
                ];
            }

            // Check if cart is empty
            if ($cart->medicines->isEmpty()) {
                return [
                    'valid' => false,
                    'message' => 'Cart is empty',
                    'errors' => ['cart_empty']
                ];
            }

            // Validate pharmacy availability
            $pharmacyValidation = $this->validatePharmacyAvailability($cart->pharmacy);
            if (!$pharmacyValidation['valid']) {
                return $pharmacyValidation;
            }

            // Validate stock availability
            $stockValidation = $this->validateStockAvailability($cart);
            if (!$stockValidation['valid']) {
                return $stockValidation;
            }

            // Validate user profile
            $userValidation = $this->validateUserProfile($cart->user);
            if (!$userValidation['valid']) {
                return $userValidation;
            }

            return [
                'valid' => true,
                'message' => 'Cart is ready for checkout',
                'cart' => $cart,
                'total_amount' => $this->calculateOrderTotals($cart)['total_amount'],
                'total_items' => $this->calculateOrderTotals($cart)['total_items']
            ];

        } catch (\Exception $e) {
            Log::error('Checkout validation error: ' . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Validation failed due to system error',
                'errors' => ['system_error']
            ];
        }
    }

    /**
     * Process checkout for a cart
     */
    public function processCheckout(int $cartId, int $userId, array $checkoutData): array
    {
        return DB::transaction(function () use ($cartId, $userId, $checkoutData) {
            try {
                // Validate cart first
                $validation = $this->validateCartForCheckout($cartId, $userId);
                if (!$validation['valid']) {
                    return $validation;
                }

                $cart = $validation['cart'];

                // Reserve stock (placeholder - will be implemented in InventoryService)
                $stockReservation = $this->reserveStock($cart);
                if (!$stockReservation['success']) {
                    return [
                        'success' => false,
                        'message' => 'Failed to reserve stock: ' . $stockReservation['message'],
                        'errors' => ['stock_reservation_failed']
                    ];
                }

                // Convert cart to order
                $order = $this->convertCartToOrder($cart, $checkoutData);
                if (!$order) {
                    // Release reserved stock (placeholder)
                    $this->releaseStock($cart);
                    return [
                        'success' => false,
                        'message' => 'Failed to create order',
                        'errors' => ['order_creation_failed']
                    ];
                }

                // Update stock after successful order creation (placeholder)
                $this->updateStockAfterOrder($order);

                // Delete the cart
                $cart->medicines()->delete();
                $cart->delete();

                Log::info("Checkout successful for cart {$cartId}, order {$order->id}");

                return [
                    'success' => true,
                    'message' => 'Checkout completed successfully',
                    'order' => $order,
                    'order_id' => $order->id
                ];

            } catch (\Exception $e) {
                Log::error('Checkout processing error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Checkout failed due to system error',
                    'errors' => ['system_error']
                ];
            }
        });
    }

    /**
     * Calculate order totals
     */
    public function calculateOrderTotals(Order $cart): array
    {
        $totalAmount = 0;
        $totalItems = 0;

        foreach ($cart->medicines as $item) {
            $subtotal = $item->price_at_time * $item->quantity;
            $totalAmount += $subtotal;
            $totalItems += $item->quantity;
        }

        return [
            'total_amount' => round($totalAmount, 2),
            'total_items' => $totalItems,
            'subtotal' => $totalAmount,
            'tax' => 0, // Can be added later
            'shipping' => 0, // Can be added later
        ];
    }

    /**
     * Validate pharmacy availability
     */
    protected function validatePharmacyAvailability(PharmacyProfile $pharmacy): array
    {
        if (!$pharmacy->verified) {
            return [
                'valid' => false,
                'message' => 'Pharmacy is not verified',
                'errors' => ['pharmacy_not_verified']
            ];
        }

        if ($pharmacy->status !== 'active') {
            return [
                'valid' => false,
                'message' => 'Pharmacy is not currently accepting orders',
                'errors' => ['pharmacy_inactive']
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate stock availability
     */
    protected function validateStockAvailability(Order $cart): array
    {
        $unavailableItems = [];

        foreach ($cart->medicines as $item) {
            $stock = StockBatch::where('pharmacy_id', $cart->pharmacy_id)
                ->where('medicine_id', $item->medicine_id)
                ->first();

            if (!$stock || $stock->quantity < $item->quantity) {
                $unavailableItems[] = [
                    'medicine_id' => $item->medicine_id,
                    'medicine_name' => $item->medicine->brand_name ?? 'Unknown',
                    'requested_quantity' => $item->quantity,
                    'available_quantity' => $stock ? $stock->quantity : 0
                ];
            }
        }

        if (!empty($unavailableItems)) {
            return [
                'valid' => false,
                'message' => 'Some items are no longer available in the requested quantities',
                'errors' => ['insufficient_stock'],
                'unavailable_items' => $unavailableItems
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate user profile
     */
    protected function validateUserProfile(User $user): array
    {
        if (!$user->email_verified_at) {
            return [
                'valid' => false,
                'message' => 'Please verify your email address before checkout',
                'errors' => ['email_not_verified']
            ];
        }

        // Add more user validation as needed
        return ['valid' => true];
    }

    /**
     * Convert cart to order
     */
    protected function convertCartToOrder(Order $cart, array $checkoutData): ?Order
    {
        try {
            $order = Order::create([
                'user_id' => $cart->user_id,
                'pharmacy_id' => $cart->pharmacy_id,
                'status' => 'pending',
                'payment_method' => $checkoutData['payment_method'] ?? 'cash',
                'billing_address' => $checkoutData['billing_address'] ?? null,
                'total_items' => $cart->total_items,
                'total_amount' => $cart->total_amount,
            ]);

            // Copy cart items to order
            foreach ($cart->medicines as $cartItem) {
                $order->medicines()->create([
                    'medicine_id' => $cartItem->medicine_id,
                    'quantity' => $cartItem->quantity,
                    'price_at_time' => $cartItem->price_at_time,
                ]);
            }

            return $order;

        } catch (\Exception $e) {
            Log::error('Order creation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get checkout summary
     */
    public function getCheckoutSummary(int $cartId, int $userId): array
    {
        $validation = $this->validateCartForCheckout($cartId, $userId);
        
        if (!$validation['valid']) {
            return $validation;
        }

        $cart = $validation['cart'];
        $totals = $this->calculateOrderTotals($cart);

        return [
            'success' => true,
            'cart' => $cart,
            'pharmacy' => $cart->pharmacy,
            'medicines' => $cart->medicines->map(function ($item) {
                return [
                    'id' => $item->medicine_id,
                    'name' => $item->medicine->brand_name ?? 'Unknown',
                    'quantity' => $item->quantity,
                    'price' => $item->price_at_time,
                    'subtotal' => $item->price_at_time * $item->quantity
                ];
            }),
            'totals' => $totals,
            'estimated_delivery' => $this->getEstimatedDelivery($cart->pharmacy_id)
        ];
    }

    /**
     * Get estimated delivery time
     */
    protected function getEstimatedDelivery(int $pharmacyId): string
    {
        // This can be enhanced with actual delivery logic
        return '1-2 business days';
    }

    /**
     * Reserve stock (placeholder - will be moved to InventoryService)
     */
    protected function reserveStock(Order $cart): array
    {
        try {
            // This is a placeholder implementation
            // Will be moved to InventoryService in Step 4
            return ['success' => true, 'message' => 'Stock reserved'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Release stock (placeholder - will be moved to InventoryService)
     */
    protected function releaseStock(Order $cart): array
    {
        try {
            // This is a placeholder implementation
            // Will be moved to InventoryService in Step 4
            return ['success' => true, 'message' => 'Stock released'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update stock after order (placeholder - will be moved to InventoryService)
     */
    protected function updateStockAfterOrder(Order $order): array
    {
        try {
            // This is a placeholder implementation
            // Will be moved to InventoryService in Step 4
            return ['success' => true, 'message' => 'Stock updated'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
