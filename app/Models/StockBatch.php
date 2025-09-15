<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatch extends Model
{
    use HasFactory;

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
        'pharmacy_id',
        'medicine_id',
        'batch_num',
        'exp_date',
        'quantity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exp_date' => 'date',
        'quantity' => 'integer',
    ];

    /**
     * The pharmacy profile that owns this stock batch.
     */
    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(PharmacyProfile::class, 'pharmacy_id');
    }

    /**
     * The medicine in this stock batch.
     */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}


