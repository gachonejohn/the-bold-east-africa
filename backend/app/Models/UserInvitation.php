<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'role',
        'otp_code',
        'otp_hash',
        'otp_expires_at',
        'status',
        'invited_by',
        'image',
        'bio',
        'department',
        'phone',
    ];

    protected $hidden = [
        'otp_hash',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
    ];

    /**
     * Generate OTP for invitation
     */
    public static function generateOtp(): array
    {
        // Generate 8-character alphanumeric OTP
        $otp = strtoupper(Str::random(8));

        return [
            'otp' => $otp,
            'hash' => Hash::make($otp),
            'expires_at' => now()->addHours(24),
        ];
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(string $otp): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        return Hash::check(strtoupper($otp), $this->otp_hash);
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired(): bool
    {
        return $this->otp_expires_at->isPast() || $this->status === 'expired';
    }

    /**
     * Check if invitation is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Mark as accepted
     */
    public function markAsAccepted(): void
    {
        $this->update(['status' => 'accepted']);
    }

    /**
     * Mark as expired
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Regenerate OTP
     */
    public function regenerateOtp(): string
    {
        $otpData = self::generateOtp();

        $this->update([
            'otp_code' => $otpData['otp'],
            'otp_hash' => $otpData['hash'],
            'otp_expires_at' => $otpData['expires_at'],
            'status' => 'pending',
        ]);

        return $otpData['otp'];
    }

    /**
     * Get inviter
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Scope pending invitations
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('otp_expires_at', '>', now());
    }

    /**
     * Scope expired invitations
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
                ->orWhere('otp_expires_at', '<=', now());
        });
    }
}
