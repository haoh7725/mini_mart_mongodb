<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const STAFF_ROLES = ['super-admin', 'manager', 'staff'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'role_id',
        'is_active',
        'password',
    ];

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
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function greenPointTransactions()
    {
        return $this->hasMany(GreenPointTransaction::class);
    }

    public function getGreenPointsAttribute(): int
    {
        return (int) $this->greenPointTransactions()->sum('points');
    }

    public function hasRole(string ...$roles): bool
    {
        return $this->role !== null && in_array($this->role->slug, $roles, true);
    }

    public function isStaff(): bool
    {
        return $this->hasRole(...self::STAFF_ROLES);
    }

    public function isShipper(): bool
    {
        return $this->hasRole('shipper');
    }

    // Tài khoản đang hoạt động có một trong các vai trò.
    public function scopeActiveWithRole($query, string ...$roles)
    {
        return $query->where('is_active', true)->whereHas('role', fn ($role) => $role->whereIn('slug', $roles));
    }
}
