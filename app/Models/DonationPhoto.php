<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationPhoto extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'donation_id',
        'photo_path',
    ];

    /**
     * Get the donation that this photo belongs to.
     */
    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }
}
