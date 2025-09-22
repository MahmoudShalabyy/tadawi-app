<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    /**
     * Get paginated orders for a user
     */
    public function getUserOrders(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Order::where('user_id', $userId)
            ->where('status', '!=', 'cart') // Exclude cart orders
            ->with(['pharmacy', 'medicines.medicine', 'payments' => function ($q) {
                $q->latest();
            }])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get specific order for a user
     */
    public function getUserOrder(int $orderId, int $userId): ?Order
    {
        return Order::where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', '!=', 'cart')
            ->with([
                'pharmacy',
                'medicines.medicine',
                'payments' => function ($q) {
                    $q->latest();
                },
                'prescriptionUploads'
            ])
            ->first();
    }

    /**
     * Get order statistics for a user
     */
    public function getUserOrderStats(int $userId): array
    {
        $orders = Order::where('user_id', $userId)
            ->where('status', '!=', 'cart')
            ->get();

        return [
            'total_orders' => $orders->count(),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'processing_orders' => $orders->where('status', 'processing')->count(),
            'completed_orders' => $orders->where('status', 'completed')->count(),
            'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
            'total_spent' => $orders->sum('total_amount'),
        ];
    }

    /**
     * Check if user can view order
     */
    public function canUserViewOrder(int $orderId, int $userId): bool
    {
        return Order::where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', '!=', 'cart')
            ->exists();
    }
}
