<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Donation extends Model
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
        'contact_info',
        'verified',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verified' => 'boolean',
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
}
