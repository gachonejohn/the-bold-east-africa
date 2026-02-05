<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'last_active',
        'image',
        'linkedin',
        'bio',
        'department',
        'phone',
        'invited_via',
        'invited_by',
        'invitation_accepted_at',
        'login_count',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * Get the user who invited this user
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get users invited by this user
     */
    public function invitedUsers()
    {
        return $this->hasMany(User::class, 'invited_by');
    }

    /**
     * Record login
     */
    public function recordLogin(string $ip = null): void
    {
        $this->update([
            'login_count' => $this->login_count + 1,
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'last_active' => now(),
        ]);
    }

    /**
     * Update last active timestamp
     */
    public function touchActivity(): void
    {
        $this->update(['last_active' => now()]);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active' => 'datetime',
            'last_login_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'login_count' => 'integer',
        ];
    }
}
