<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_name',
        'api_token',
        'subscription_status',
        'trial_started_at',
        'trial_ends_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'trial_started_at'   => 'datetime',
            'trial_ends_at'      => 'datetime',
        ];
    }

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** Apakah trial masih aktif? */
    public function isTrialActive(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    /** Apakah subscription aktif (trial atau berbayar)? */
    public function isActive(): bool
    {
        return $this->subscription_status === 'active' || $this->isTrialActive();
    }

    /** Sisa hari trial (0 jika sudah habis) */
    public function trialDaysLeft(): int
    {
        if (!$this->trial_ends_at) return 0;
        return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
    }

    /** Generate api_token unik dengan prefix EMOTEXT_ */
    public static function generateApiToken(): string
    {
        return 'EMOTEXT_' . Str::random(48);
    }
}
