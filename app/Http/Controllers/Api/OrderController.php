<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use ApiResponse;

    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Get user's order history
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return $this->unauthorized('Authentication required');
        }

        try {
            $filters = $request->only(['status', 'date_from', 'date_to', 'per_page']);
            $orders = $this->orderService->getUserOrders($user->id, $filters);
            
            return $this->success(
                OrderResource::collection($orders),
                'Orders retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve orders');
        }
    }

    /**
     * Get specific order details
     */
    public function show(Request $request, int $orderId)
    {
        $user = Auth::user();
        
        if (!$user) {
            return $this->unauthorized('Authentication required');
        }

        try {
            $order = $this->orderService->getUserOrder($orderId, $user->id);
            
            if (!$order) {
                return $this->notFound('Order not found');
            }

            return $this->success(
                new OrderResource($order),
                'Order retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve order');
        }
    }

    /**
     * Get user's order statistics
     */
    public function stats(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return $this->unauthorized('Authentication required');
        }

        try {
            $stats = $this->orderService->getUserOrderStats($user->id);
            
            return $this->success(
                $stats,
                'Order statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve order statistics');
        }
    }
}