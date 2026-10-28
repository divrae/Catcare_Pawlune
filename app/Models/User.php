<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'address',
        'is_active', 'must_change_password', 'is_walk_in',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'is_walk_in' => 'boolean',
        ];
    }

    public const STAFF_ROLES = ['admin', 'doctor', 'staff', 'cashier', 'groomer'];

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isStaffMember(): bool
    {
        return in_array($this->role, self::STAFF_ROLES, true);
    }

    public function roleLabel(): string
    {
        return config('catcare.roles.' . $this->role, $this->role);
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            'admin' => route('admin.dashboard'),
            'doctor' => route('doctor.dashboard'),
            'staff' => route('boarding.index'),
            'cashier' => route('pos.index'),
            'groomer' => route('grooming.index'),
            default => route('owner.dashboard'),
        };
    }

    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class, 'owner_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function todayShift(): ?Shift
    {
        return $this->shifts()->whereDate('shift_date', today())->first();
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $i = strtoupper(mb_substr($parts[0] ?? '?', 0, 1));
        if (isset($parts[1])) {
            $i .= strtoupper(mb_substr($parts[1], 0, 1));
        }
        return $i;
    }
}
