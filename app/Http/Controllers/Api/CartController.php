<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Models\Order;
use App\Models\OrderMedicine;
use App\Models\Medicine;
use App\Models\StockBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $pharmacyId = $request->query('pharmacy_id');

        $query = Order::where('user_id', $user->id)->where('status', 'cart');
        if ($pharmacyId) $query->where('pharmacy_id', $pharmacyId);

        $carts = $query->with(['medicines.medicine', 'pharmacy'])->get();

        $carts->each(function ($cart) {
            $cart->medicines->each(function ($item) use ($cart) {
                $stock = StockBatch::where('pharmacy_id', $cart->pharmacy_id)
                    ->where('medicine_id', $item->medicine_id)
                    ->first();

                $item->is_available = $stock && $stock->quantity >= $item->quantity;
                $item->stock_status = $stock ? ($stock->quantity == 0 ? 'out_of_stock' : ($stock->quantity < 5 ? 'low_stock' : 'in_stock')) : 'unknown';
                $item->available_stock = $stock ? $stock->quantity : 0;
                $item->subtotal = $item->price_at_time * $item->quantity;
            });

            $cart->total_amount = $cart->medicines->sum('subtotal');
            $cart->total_items = $cart->medicines->sum('quantity');
        });

        return response()->json(['success' => true, 'data' => $carts]);
    }

    public function store(AddToCartRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        $medicine = Medicine::find($validated['medicine_id']);
        $stock = StockBatch::where('pharmacy_id', $validated['pharmacy_id'])
            ->where('medicine_id', $validated['medicine_id'])
            ->first();

        if (!$stock || $stock->quantity < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => "Only {$stock->quantity} available (requested: {$validated['quantity']})",
                'available_stock' => $stock->quantity,
            ], 400);
        }

        $cart = Order::firstOrCreate(
            ['user_id' => $user->id, 'pharmacy_id' => $validated['pharmacy_id'], 'status' => 'cart'],
            ['total_amount' => 0.00, 'total_items' => 0]
        );

        $existing = $cart->medicines()->where('medicine_id', $validated['medicine_id'])->first();
        $newQuantity = $existing ? $existing->quantity + $validated['quantity'] : $validated['quantity'];

        if ($newQuantity > $stock->quantity) {
            return response()->json([
                'success' => false,
                'message' => "Cannot add {$validated['quantity']}. Only {$stock->quantity} available. Current in cart: " . ($existing ? $existing->quantity : 0),
                'available_stock' => $stock->quantity,
                'current_in_cart' => $existing ? $existing->quantity : 0,
            ], 400);
        }

        $currentTotal = $cart->medicines()->sum('quantity') - ($existing ? $existing->quantity : 0) + $newQuantity;
        if ($currentTotal > 10) {
            return response()->json([
                'success' => false,
                'message' => "Cannot add. Cart max 10 items. Current total: " . ($currentTotal - $newQuantity),
                'current_total' => $currentTotal - $newQuantity,
                'max_allowed' => 10,
            ], 400);
        }

        $price = $medicine->price;

        if ($existing) {
            $existing->update(['quantity' => $newQuantity]);
        } else {
            $cart->medicines()->create([
                'medicine_id' => $validated['medicine_id'],
                'quantity' => $validated['quantity'],
                'price_at_time' => $price,
            ]);
        }

        $cart->save();

        $updatedCart = $cart->load(['medicines.medicine']);
        return response()->json(['success' => true, 'message' => 'Added to cart', 'data' => $updatedCart]);
    }

    public function update(UpdateCartRequest $request, OrderMedicine $item)
    {
        $user = Auth::user();
        if ($item->order->user_id !== $user->id || $item->order->status !== 'cart') {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $validated = $request->validated();

        $stock = StockBatch::where('pharmacy_id', $item->order->pharmacy_id)
            ->where('medicine_id', $item->medicine_id)
            ->first();

        if (!$stock || $stock->quantity < $validated['quantity']) {
            return response()->json(['success' => false, 'message' => "Only {$stock->quantity} available"], 400);
        }

        $currentTotal = $item->order->medicines()->sum('quantity') - $item->quantity + $validated['quantity'];
        if ($currentTotal > 10) {
            return response()->json(['success' => false, 'message' => 'Max 10 items'], 400);
        }

        $item->update(['quantity' => $validated['quantity']]);
        $item->order->save();

        $updatedCart = $item->order->load(['medicines.medicine']);
        return response()->json(['success' => true, 'message' => 'Updated', 'data' => $updatedCart]);
    }

    public function destroy(OrderMedicine $item)
    {
        $user = Auth::user();
        if ($item->order->user_id !== $user->id || $item->order->status !== 'cart') {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $cart = $item->order;
        $item->delete();

        if ($cart->medicines()->count() === 0) {
            $cart->delete();
        }

        return response()->json(['success' => true, 'message' => 'Removed', 'data' => $cart->load(['medicines.medicine']) ?? []]);
    }

   public function clear(Request $request)
{
    $user = Auth::user();
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
    }

    $pharmacyId = $request->query('pharmacy_id');

    $cart = Order::where('user_id', $user->id)->where('status', 'cart');
    if ($pharmacyId) {
        $cart->where('pharmacy_id', $pharmacyId);
    }
    $cart = $cart->first();

    \Log::info('Clear Cart Query Result: ' . ($cart ? $cart->id : 'null') . ' for user_id: ' . $user->id . ' pharmacy_id: ' . ($pharmacyId ?: 'none'));

    if (!$cart) {
        return response()->json([
            'success' => false,
            'message' => 'No cart found for this user' . ($pharmacyId ? " or pharmacy $pharmacyId" : ''),
            'data' => []
        ], 404);
    }

    $cart->medicines()->delete();
    $cart->delete();

    return response()->json(['success' => true, 'message' => 'Cleared', 'data' => []]);
}

    public function recommendations(Request $request)
    {
        $user = Auth::user();
        $pharmacyId = $request->query('pharmacy_id');

        $cart = Order::where('user_id', $user->id)
            ->where('pharmacy_id', $pharmacyId)
            ->where('status', 'cart')
            ->with([
                'medicines.medicine' => function ($query) {
                    $query->with(['therapeuticClasses', 'activeIngredient']);
                }
            ])
            ->first();

        if (!$cart || $cart->medicines->isEmpty()) {
            return response()->json(['success' => true, 'data' => [], 'message' => 'Add medicines for recommendations']);
        }

        $classes = $cart->medicines->pluck('medicine.therapeuticClasses.*.id')->flatten()->unique();
        $ingredients = $cart->medicines->pluck('medicine.activeIngredient.id')->unique()->filter();
        $inCartIds = $cart->medicines->pluck('medicine_id');

        $recs = Medicine::whereNotIn('id', $inCartIds)
            ->where(function ($q) use ($classes, $ingredients) {
                if ($classes->isNotEmpty()) {
                    $q->whereHas('therapeuticClasses', function ($sub) use ($classes) {
                        $sub->whereIn('id', $classes);
                    });
                }
                if ($ingredients->isNotEmpty()) {
                    $q->orWhereHas('activeIngredient', function ($sub) use ($ingredients) {
                        $sub->whereIn('id', $ingredients);
                    });
                }
                if ($classes->isEmpty() && $ingredients->isEmpty()) {
                    $q->whereRaw('1=0'); // No recommendations if no basis
                }
            })
            ->whereHas('stockBatches', fn($q) => $q->where('pharmacy_id', $pharmacyId)->where('quantity', '>', 0))
            ->with(['therapeuticClasses', 'activeIngredient'])
            ->limit(6)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $recs,
            'based_on' => ['classes' => $classes->values(), 'ingredients' => $ingredients->values()]
        ]);
    }
}