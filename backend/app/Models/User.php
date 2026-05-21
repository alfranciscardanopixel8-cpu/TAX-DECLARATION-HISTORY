<?php

namespace App\Models;

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
        ];
    }

    public function canManageRecords(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_ASSESSOR,
            self::ROLE_RECORDS_STAFF,
        ], true);
    }

    public function canApproveRecords(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_ASSESSOR,
        ], true);
    }

    public function canAdminister(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
}
