<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'location',
        'latitude',
        'longitude',
        'contact_info',
        'verified',
        'rating',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verified' => 'boolean',
        'rating' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the user that owns the pharmacy profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the stock batches for this pharmacy.
     */
    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class, 'pharmacy_id');
    }

    /**
     * Get the orders for this pharmacy.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'pharmacy_id');
    }

    /**
     * Get the reviews for this pharmacy.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'pharmacy_id');
    }
}


