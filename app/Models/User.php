<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'profile_picture_path',
        'role',
        'status',
        'travel_mode',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the patient profile associated with the user.
     */
    public function patientProfile(): HasOne
    {
        return $this->hasOne(PatientProfile::class);
    }

    /**
     * Get the pharmacy profile associated with the user.
     */
     public function pharmacyProfile(): HasOne
     {
         return $this->hasOne(PharmacyProfile::class);
     }

    /**
     * Get the doctor profile associated with the user.
     */
    public function doctorProfile(): HasOne
    {
        return $this->hasOne(DoctorProfile::class);
    }

    /**
     * Get the orders placed by this user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the donations made by this user.
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Get the reviews written by this user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}