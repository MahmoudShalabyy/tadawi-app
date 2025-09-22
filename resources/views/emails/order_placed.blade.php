<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; }
        .container { max-width: 640px; margin: 0 auto; padding: 16px; }
        .header { border-bottom: 1px solid #eee; margin-bottom: 16px; }
        .muted { color: #666; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #eee; }
        .total-row td { font-weight: bold; }
    </style>
    </head>
<body>
    <div class="container">
        <div class="header">
            <h2>Thank you for your order</h2>
            <p class="muted">Order #: {{ $order->order_number ?? $order->id }} • Placed {{ $order->created_at?->format('Y-m-d H:i') }}</p>
        </div>

        <p>Hello {{ $order->user->name ?? 'Customer' }},</p>
        <p>Your order has been placed and is now {{ $order->status }}.</p>

        <h3>Order summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->medicines as $item)
                    <tr>
                        <td>{{ $item->medicine->brand_name ?? ('#'.$item->medicine_id) }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $order->currency ?? 'EGP' }} {{ number_format((float)$item->price_at_time, 2) }}</td>
                        <td>{{ $order->currency ?? 'EGP' }} {{ number_format((float)$item->price_at_time * (int)$item->quantity, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3">Total</td>
                    <td>{{ $order->currency ?? 'EGP' }} {{ number_format((float)$order->total_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <h3>Addresses</h3>
        <p>
            <strong>Billing:</strong> {{ $order->billing_address ?? 'N/A' }}<br>
            <strong>Shipping:</strong> {{ $order->shipping_address ?? ($order->billing_address ?? 'N/A') }}
        </p>

        <h3>Payment</h3>
        <p>
            Method: {{ $latestPayment->method ?? $order->payment_method }}<br>
            Status: {{ $latestPayment->status ?? 'pending' }}<br>
            @if(!empty($latestPayment?->transaction_id))
                Transaction ID: {{ $latestPayment->transaction_id }}
            @endif
        </p>

        <p class="muted">If you have any questions, reply to this email.</p>
    </div>
</body>
</html>


