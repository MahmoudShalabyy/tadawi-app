<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected int $orderId;

    /**
     * Create a new message instance.
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $order = Order::with(['user', 'pharmacy', 'medicines.medicine', 'payments' => function ($q) {
            $q->latest();
        }])->findOrFail($this->orderId);

        $latestPayment = $order->payments->first();

        return $this->subject('Your order #' . ($order->order_number ?? $order->id) . ' has been placed')
            ->view('emails.order_placed')
            ->with([
                'order' => $order,
                'latestPayment' => $latestPayment,
            ]);
    }
}


