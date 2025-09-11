<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'payment_method' => 'string',
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
     * Get the medicines in this order.
     */
    public function medicines(): BelongsToMany
    {
        return $this->belongsToMany(Medicine::class, 'order_medicines')
                    ->withPivot('quantity');
    }

    /**
     * Get the prescription uploads for this order.
     */
    public function prescriptionUploads(): HasMany
    {
        return $this->hasMany(PrescriptionUpload::class);
    }
}
