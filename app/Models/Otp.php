<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Otp extends Model
{
    protected $fillable = [
        'email', 
        'otp_hash', 
        'type', 
        'expires_at', 
        'used', 
        'used_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'used' => 'boolean',
    ];

    /**
     * Check if the OTP has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at < Carbon::now();
    }

    /**
     * Check if the OTP is valid (not used and not expired).
     */
    public function isValid(): bool
    {
        return !$this->used && !$this->isExpired();
    }

    /**
     * Verify the provided OTP code against the stored hash.
     */
    public function verifyOtp(string $otpCode): bool
    {
        return Hash::check($otpCode, $this->otp_hash);
    }

    /**
     * Mark the OTP as used.
     */
    public function markAsUsed(): void
    {
        $this->update([
            'used' => true,
            'used_at' => Carbon::now()
        ]);
    }

    /**
     * Generate a new OTP for the given email and type.
     * Includes rate limiting and security measures.
     */
    public static function generateOtp(string $email, string $type = 'verification'): self
    {
        // Rate limiting: Check if user has requested OTP recently
        $rateLimitKey = "otp_rate_limit:{$email}:{$type}";
        if (Cache::has($rateLimitKey)) {
            throw new \Exception('Please wait before requesting another OTP.');
        }

        // Delete old OTPs for this email and type
        self::where('email', $email)
            ->where('type', $type)
            ->delete();

        // Generate 6-digit OTP
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Create OTP record with hashed code
        $otp = self::create([
            'email' => $email,
            'otp_hash' => Hash::make($otpCode),
            'type' => $type,
            'expires_at' => Carbon::now()->addMinutes(10), // 10 minutes expiry
        ]);

        // Set rate limit: 60 seconds cooldown
        Cache::put($rateLimitKey, true, 60);

        // Store the plain OTP temporarily for email sending (will be cleared after use)
        Cache::put("otp_plain:{$otp->id}", $otpCode, 600); // 10 minutes

        return $otp;
    }

    /**
     * Get the plain OTP code for email sending.
     * This should only be called immediately after generation.
     */
    public function getPlainOtp(): ?string
    {
        return Cache::get("otp_plain:{$this->id}");
    }

    /**
     * Find and verify an OTP.
     */
    public static function findAndVerify(string $email, string $otpCode, string $type = 'verification'): ?self
    {
        $otp = self::where('email', $email)
                   ->where('type', $type)
                   ->where('used', false)
                   ->first();

        if (!$otp || !$otp->isValid() || !$otp->verifyOtp($otpCode)) {
            return null;
        }

        return $otp;
    }

    /**
     * Clean up expired OTPs (can be called via scheduled task).
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', Carbon::now())
                   ->orWhere('used', true)
                   ->delete();
    }
}