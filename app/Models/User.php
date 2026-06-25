<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'name',
        'email',
        'mobile',
        'password',
        'status',
        'last_login_at',
    ];

    protected $appends = [
        'selected_academic_year_id',
    ];

    /**
     * Get user's selected academic year ID.
     */
    public function getSelectedAcademicYearIdAttribute()
    {
        return \App\Support\AcademicYearContext::getWebAcademicYearId();
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
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the school that the user belongs to.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Check if user is Super Admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    /**
     * Check if user is currently impersonated
     */
    public function isImpersonated(): bool
    {
        return session()->has('impersonator_user_id');
    }

    /**
     * Override Spatie's role resolver to scope by user's school_id.
     */
    protected function getStoredRole($role): \Spatie\Permission\Contracts\Role
    {
        $roleClass = $this->getRoleClass();

        if (is_numeric($role)) {
            return $roleClass::findById($role, $this->getDefaultGuardName());
        }

        if (is_string($role)) {
            $foundRole = $roleClass::where('name', $role)
                ->where('guard_name', $this->getDefaultGuardName())
                ->where('school_id', $this->school_id)
                ->first();

            if (!$foundRole) {
                $foundRole = $roleClass::where('name', $role)
                    ->where('guard_name', $this->getDefaultGuardName())
                    ->whereNull('school_id')
                    ->first();
            }

            if (!$foundRole) {
                throw \Spatie\Permission\Exceptions\RoleDoesNotExist::named($role, $this->getDefaultGuardName());
            }

            return $foundRole;
        }

        return $role;
    }
}
