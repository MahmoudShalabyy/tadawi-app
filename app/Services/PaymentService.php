<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlaced;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Process payment for an order
     */
    public function processPayment(Order $order, array $paymentData): array
    {
        try {
            // Calculate order total from medicines if order total is 0
            $orderTotal = $order->total_amount;
            if ($orderTotal <= 0) {
                $orderTotal = $order->medicines->sum(function ($item) {
                    return $item->price_at_time * $item->quantity;
                });
            }

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'currency' => $paymentData['currency'] ?? 'EGP',
                'method' => $paymentData['method'],
                'status' => 'pending',
                'gateway_response' => null,
                'transaction_id' => $paymentData['payment_id'] ?? null,
            ]);

            // Process payment based on method
            $result = $this->handlePaymentByMethod($payment, $paymentData);
            
            if (!$result['success']) {
                $this->updatePaymentStatus($payment, 'failed', $result['message']);
                return $result;
            }

            // Update payment with transaction ID from result
            $payment->update([
                'transaction_id' => $result['transaction_id'] ?? $payment->transaction_id
            ]);

            // Update payment status to completed
            $this->updatePaymentStatus($payment, 'completed', 'Payment processed successfully');

            // Update order status
            $this->updateOrderStatus($order, 'processing');

            // Send order confirmation email (queued)
            try {
                Mail::to($order->user->email)->queue(new OrderPlaced($order->id));
            } catch (\Exception $e) {
                Log::warning('OrderPlaced email failed to queue: ' . $e->getMessage());
            }

            Log::info("Payment processed successfully for order {$order->id}, payment {$payment->id}");

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment_id' => $payment->id,
                'transaction_id' => $result['transaction_id'] ?? null,
                'payment_method' => $paymentData['method']
            ];

        } catch (\Exception $e) {
            Log::error('Payment processing error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment processing failed due to system error',
                'errors' => ['system_error']
            ];
        }
    }

    /**
     * Handle payment by method
     */
    protected function handlePaymentByMethod(Payment $payment, array $paymentData): array
    {
        switch ($payment->method) {
            case 'cash':
                return $this->processCashPayment($payment, $paymentData);
            case 'paypal':
                return $this->processPayPalPayment($payment, $paymentData);
            default:
                return [
                    'success' => false,
                    'message' => 'Unsupported payment method',
                    'errors' => ['unsupported_method']
                ];
        }
    }

    /**
     * Process cash payment
     */
    protected function processCashPayment(Payment $payment, array $paymentData): array
    {
        return [
            'success' => true,
            'message' => 'Cash payment accepted',
            'transaction_id' => 'CASH_' . $payment->id . '_' . time()
        ];
    }

    /**
     * Process PayPal payment
     */
    protected function processPayPalPayment(Payment $payment, array $paymentData): array
    {
        // For PayPal, we assume the payment is already verified by PayPal
        return [
            'success' => true,
            'message' => 'PayPal payment processed',
            'transaction_id' => $paymentData['payment_id'] ?? 'PAYPAL_' . $payment->id . '_' . time()
        ];
    }

    /**
     * Handle PayPal webhook
     */
    public function handlePayPalWebhook(array $webhookData): array
    {
        try {
            $eventType = $webhookData['event_type'] ?? null;
            $resource = $webhookData['resource'] ?? [];

            switch ($eventType) {
                case 'PAYMENT.SALE.COMPLETED':
                    return $this->handlePaymentCompleted($resource);
                case 'PAYMENT.SALE.DENIED':
                    return $this->handlePaymentDenied($resource);
                case 'PAYMENT.SALE.REFUNDED':
                    return $this->handlePaymentRefunded($resource);
                default:
                    return [
                        'success' => true,
                        'message' => 'Webhook event not handled',
                        'event_type' => $eventType
                    ];
            }

        } catch (\Exception $e) {
            Log::error('PayPal webhook error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Webhook processing failed',
                'errors' => ['webhook_error']
            ];
        }
    }

    /**
     * Handle payment completed webhook
     */
    protected function handlePaymentCompleted(array $resource): array
    {
        $transactionId = $resource['id'] ?? null;

        $payment = Payment::where('transaction_id', $transactionId)->first();
        if ($payment) {
            $this->updatePaymentStatus($payment, 'completed', 'Payment completed via PayPal');
            $this->updateOrderStatus($payment->order, 'processing');
        }

        return [
            'success' => true,
            'message' => 'Payment completed',
            'transaction_id' => $transactionId
        ];
    }

    /**
     * Handle payment denied webhook
     */
    protected function handlePaymentDenied(array $resource): array
    {
        $transactionId = $resource['id'] ?? null;

        $payment = Payment::where('transaction_id', $transactionId)->first();
        if ($payment) {
            $this->updatePaymentStatus($payment, 'failed', 'Payment denied via PayPal');
        }

        return [
            'success' => true,
            'message' => 'Payment denied',
            'transaction_id' => $transactionId
        ];
    }

    /**
     * Handle payment refunded webhook
     */
    protected function handlePaymentRefunded(array $resource): array
    {
        $transactionId = $resource['id'] ?? null;

        $payment = Payment::where('transaction_id', $transactionId)->first();
        if ($payment) {
            $this->updatePaymentStatus($payment, 'refunded', 'Payment refunded via PayPal');
        }

        return [
            'success' => true,
            'message' => 'Payment refunded',
            'transaction_id' => $transactionId
        ];
    }

    /**
     * Update payment status
     */
    protected function updatePaymentStatus(Payment $payment, string $status, string $message = null): void
    {
        $payment->update([
            'status' => $status,
            'gateway_response' => $message,
            'updated_at' => now()
        ]);
    }

    /**
     * Update order status
     */
    protected function updateOrderStatus(Order $order, string $status): void
    {
        $order->update(['status' => $status]);
    }

}
