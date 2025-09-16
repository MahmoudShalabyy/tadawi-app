<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'location',
        'contact_info',
        'verified',
        'status',
        'sealed_confirmed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verified' => 'boolean',
        'status' => 'string',
        'sealed_confirmed' => 'boolean',
    ];

    /**
     * Get the user that made this donation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the medicines in this donation.
     */
    public function medicines(): BelongsToMany
    {
        return $this->belongsToMany(Medicine::class, 'donation_medicines')
                    ->withPivot(['quantity', 'expiry_date', 'batch_num']);
    }

    /**
     * Get the photos for this donation.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(DonationPhoto::class);
    }

    // Status constants
    const STATUS_PROPOSED = 'proposed';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COLLECTED = 'collected';

    // Scope methods
    public function scopeProposed($query)
    {
        return $query->where('status', self::STATUS_PROPOSED);
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeCollected($query)
    {
        return $query->where('status', self::STATUS_COLLECTED);
    }
}
