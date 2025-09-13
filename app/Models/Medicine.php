<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'brand_name',
        'form',
        'dosage_strength',
        'manufacturer',
        'price',
        'active_ingredient_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get the active ingredient for this medicine.
     */
    public function activeIngredient(): BelongsTo
    {
        return $this->belongsTo(ActiveIngredient::class);
    }

    /**
     * Get the therapeutic classes that this medicine belongs to.
     */
    public function therapeuticClasses(): BelongsToMany
    {
        return $this->belongsToMany(TherapeuticClass::class, 'medicine_classes')
                    ->withPivot('note');
    }

    /**
     * Get the stock batches for this medicine.
     */
    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    /**
     * Get the orders that include this medicine.
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_medicines')
                    ->withPivot('quantity');
    }

    /**
     * Get the donations that include this medicine.
     */
    public function donations(): BelongsToMany
    {
        return $this->belongsToMany(Donation::class, 'donation_medicines')
                    ->withPivot(['quantity', 'expiry_date', 'batch_num']);
    }
}


