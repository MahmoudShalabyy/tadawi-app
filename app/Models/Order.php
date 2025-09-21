<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'pharmacy_id',
        'status',
        'payment_method',
        'billing_address',
        'total_items', // إضافة لتخزين العدد الكلي
        'total_amount', // إضافة لتخزين المجموع
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'payment_method' => 'string',
        'total_items' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the user that placed this order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the pharmacy that will fulfill this order.
     */
    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(PharmacyProfile::class, 'pharmacy_id');
    }

    /**
     * Get the medicines in this order (via OrderMedicine).
     */
    public function medicines(): HasMany
    {
        return $this->hasMany(OrderMedicine::class);
    }

    /**
     * Get the prescription uploads for this order.
     */
    public function prescriptionUploads(): HasMany
    {
        return $this->hasMany(PrescriptionUpload::class);
    }

    /**
     * Get or create cart for user and pharmacy.
     */
    public static function getCart($userId, $pharmacyId)
    {
        return self::firstOrCreate(
            ['user_id' => $userId, 'pharmacy_id' => $pharmacyId, 'status' => 'cart'],
            ['total_items' => 0, 'total_amount' => 0.00]
        );
    }

    /**
     * Calculate totals and validate max items before saving.
     */
    protected static function booted()
    {
        static::saving(function ($order) {
            if ($order->status === 'cart') {
                $order->total_items = $order->medicines->sum('quantity') ?? 0;
                $order->total_amount = $order->medicines->sum(function ($item) {
                    return $item->price_at_time * $item->quantity;
                }) ?? 0;

                if ($order->total_items > 10) {
                    throw new \Exception('Cart cannot contain more than 10 total items');
                }
            }
        });
    }
}