<?php

namespace App\Models;

use App\Support\Permissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_ASSESSOR = 'assessor';
    public const ROLE_RECORDS_STAFF = 'records_staff';
    public const ROLE_VIEWER = 'viewer';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'permission_grants',
        'permission_denies',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permission_grants' => 'array',
            'permission_denies' => 'array',
        ];
    }

    /**
     * Effective permission set: role defaults + grants - denies.
     */
    public function permissions(): array
    {
        return Permissions::effective(
            $this->role,
            $this->permission_grants ?? [],
            $this->permission_denies ?? []
        );
    }

    public function can_($permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    public function canManageRecords(): bool
    {
        return $this->can_('property.update') || $this->can_('property.create');
    }

    public function canApproveRecords(): bool
    {
        return $this->can_('property.approve') || $this->can_('td.approve');
    }

    public function canAdminister(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->can_('user.update');
    }
}
