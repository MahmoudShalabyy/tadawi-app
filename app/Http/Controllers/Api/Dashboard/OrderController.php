<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\OrderUpdateRequest;
use App\Http\Resources\Dashboard\OrderResource;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    use ApiResponse;
    /**
     * Get paginated list of orders with advanced joins and filtering
     */
    public function index(Request $request)
    {
        try {
            $query = Order::select([
                'orders.id',
                'orders.user_id',
                'orders.pharmacy_id',
                'orders.status',
                'orders.payment_method',
                'orders.billing_address',
                'orders.created_at',
                'orders.updated_at',
                'users.name as patient_name',
                'pharmacy_profiles.location as pharmacy_location'
            ])
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('pharmacy_profiles', 'orders.pharmacy_id', '=', 'pharmacy_profiles.user_id')
            ->where('users.status', 'active')
            ->where('pharmacy_profiles.verified', true)
            ->with(['medicines' => function ($query) {
                $query->select('medicines.id', 'medicines.brand_name', 'order_medicines.quantity');
            }])
            ->orderBy('orders.created_at', 'desc');

            // Apply status filter
            if ($request->has('status') && !empty($request->status)) {
                $query->where('orders.status', $request->status);
            }

            // Apply date filter
            if ($request->has('created_at') && !empty($request->created_at)) {
                $query->whereDate('orders.created_at', $request->created_at);
            }

            // Use paginateData helper for consistent pagination
            $paginatedData = paginateData($query, 10, null, []);

            // Transform data using OrderResource
            $transformedData = collect($paginatedData['data'])->map(function ($order) {
                // Create a mock Order object for the resource
                $mockOrder = new Order([
                    'id' => $order->id,
                    'user_id' => $order->user_id,
                    'pharmacy_id' => $order->pharmacy_id,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'billing_address' => $order->billing_address,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]);

                // Add patient name and pharmacy location to the mock object
                $mockOrder->patient_name = $order->patient_name;
                $mockOrder->pharmacy_location = $order->pharmacy_location;

                return new OrderResource($mockOrder);
            });

            // Update the data in the paginated response
            $paginatedData['data'] = $transformedData;

            return $this->success($paginatedData, 'Orders retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve orders: ' . $e->getMessage());
        }
    }

    /**
     * Get orders for the authenticated user
     */
    public function myOrders(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $searchTerm = $request->input('search');

        $query = Order::where('user_id', $request->user()->id)
            ->with(['medicines', 'pharmacy', 'prescriptionUploads'])
            ->orderBy('created_at', 'desc');

        $searchFields = ['status', 'billing_address'];

        $result = paginateData($query, $perPage, $searchTerm, $searchFields);

        // Transform the data using the resource
        $result['data'] = OrderResource::collection($result['data']);

        return $this->success($result, 'Your orders retrieved successfully');
    }

    /**
     * Update the specified order
     *
     * @param OrderUpdateRequest $request
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(OrderUpdateRequest $request, Order $order)
    {
        try {
            $validatedData = $request->validated();

            // Use database transaction to ensure data consistency
            $updatedOrder = DB::transaction(function () use ($order, $validatedData) {
                // Update the order
                $order->update($validatedData);

                return $order->fresh();
            });

            // Load the order with its relationships
            $updatedOrder->load(['user', 'medicines', 'pharmacy']);

            // Send email notifications
            $this->sendOrderUpdateNotifications($updatedOrder);

            // Transform the response using OrderResource
            $orderResource = new OrderResource($updatedOrder);

            return $this->success($orderResource, 'Order updated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to update order: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete the specified order
     *
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Order $order)
    {
        try {
            // Check if order is already soft deleted
            if ($order->trashed()) {
                return $this->error('Order is already deleted', 404);
            }

            // Soft delete the order
            $order->delete();

            return $this->success(null, 'Order deleted successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to delete order: ' . $e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted order
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {
        try {
            // Find the order including soft-deleted ones
            $order = Order::withTrashed()->find($id);

            if (!$order) {
                return $this->error('Order not found', 404);
            }

            // Check if order is actually soft deleted
            if (!$order->trashed()) {
                return $this->error('Order is not deleted', 400);
            }

            // Restore the order
            $order->restore();

            // Load the order with its relationships
            $order->load(['user', 'medicines', 'pharmacy']);

            // Transform the response using OrderResource
            $orderResource = new OrderResource($order);

            return $this->success($orderResource, 'Order restored successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to restore order: ' . $e->getMessage());
        }
    }

    /**
     * Get order statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        try {
            // Payment method distribution (count per payment_method)
            $paymentMethodDistribution = Order::selectRaw('payment_method, COUNT(*) as count')
                ->groupBy('payment_method')
                ->pluck('count', 'payment_method')
                ->toArray();

            // Orders over time by status (group by created_at date and status)
            $ordersOverTime = Order::selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30)) // Last 30 days
                ->groupBy(['date', 'status'])
                ->orderBy('date', 'desc')
                ->get()
                ->groupBy('date')
                ->map(function ($dayOrders) {
                    return $dayOrders->pluck('count', 'status')->toArray();
                });

            // Prepare statistics data
            $stats = [
                'payment_method_distribution' => $paymentMethodDistribution,
                'orders_over_time_by_status' => $ordersOverTime,
                'total_orders' => Order::count(),
                'period' => 'Last 30 days',
            ];

            return $this->success($stats, 'Order statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve order statistics: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific order by ID
     */
    public function show(Order $order)
    {
        $order->load(['user', 'medicines', 'pharmacy', 'prescriptionUploads']);

        return $this->success(['order' => new OrderResource($order)], 'Order retrieved successfully');
    }

    /**
     * Send order update notifications to user and pharmacy
     *
     * @param Order $order
     * @return void
     */
    private function sendOrderUpdateNotifications(Order $order): void
    {
        try {
            // Send notification to user
            if ($order->user) {
                $this->sendUserNotification($order->user, $order);
            }

            // Send notification to pharmacy
            if ($order->pharmacy) {
                $this->sendPharmacyNotification($order->pharmacy, $order);
            }

        } catch (\Exception $e) {
            \Log::error("Failed to send order update notifications: " . $e->getMessage());
        }
    }

    /**
     * Send notification email to user
     *
     * @param \App\Models\User $user
     * @param \App\Models\Order $order
     * @return void
     */
    private function sendUserNotification($user, $order): void
    {
        try {
            $subject = 'Order Update - Tadawi';
            $message = "Dear {$user->name},\n\n";
            $message .= "Your order #{$order->id} has been updated.\n\n";
            $message .= "Status: {$order->status}\n";
            $message .= "Payment Method: {$order->payment_method}\n";
            if ($order->billing_address) {
                $message .= "Billing Address: {$order->billing_address}\n";
            }
            $message .= "\nThank you for using Tadawi!";

            \Log::info("Order update notification sent to user {$user->email}: {$subject}");

            // In a real implementation, you would use:
            // Mail::to($user->email)->send(new OrderUpdateMail($order));

        } catch (\Exception $e) {
            \Log::error("Failed to send user notification: " . $e->getMessage());
        }
    }

    /**
     * Send notification email to pharmacy
     *
     * @param \App\Models\PharmacyProfile $pharmacy
     * @param \App\Models\Order $order
     * @return void
     */
    private function sendPharmacyNotification($pharmacy, $order): void
    {
        try {
            $subject = 'Order Update - Tadawi Pharmacy';
            $message = "Dear Pharmacy Team,\n\n";
            $message .= "Order #{$order->id} has been updated.\n\n";
            $message .= "Status: {$order->status}\n";
            $message .= "Payment Method: {$order->payment_method}\n";
            if ($order->billing_address) {
                $message .= "Billing Address: {$order->billing_address}\n";
            }
            $message .= "\nPlease check your dashboard for more details.";

            \Log::info("Order update notification sent to pharmacy: {$subject}");

            // In a real implementation, you would use:
            // Mail::to($pharmacy->contact_info)->send(new OrderUpdateMail($order));

        } catch (\Exception $e) {
            \Log::error("Failed to send pharmacy notification: " . $e->getMessage());
        }
    }
}
