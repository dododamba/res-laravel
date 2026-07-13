<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Les attributs assignables en masse.
     */
    protected $fillable = [
        'uuid',
        'email',
        'password',
        'avatar',
        'firstname',
        'lastname',
        'is_verified',
        'slug',
        'telephone',
        'fonction',
        'status',
        'is_active',
        'is_locked',
        'login_attempts',
        'locked_until',
        'last_login',
    ];

    /**
     * Les attributs masqués pour la sérialisation.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Les attributs castés dans des types natifs.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'locked_until' => 'datetime',
        'last_login' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
            if (empty($user->id)) {
                $user->id = $user->uuid;
            }
            if (empty($user->slug)) {
                $user->slug = 'uuid-' . Str::random(10);
            }
        });
    }

    /**
     * Relation One-to-One avec le profil d'Agent (Enquêteur, Délégué, etc.)
     */
    public function agent()
    {
        return $this->hasOne(Agent::class, 'user_id');
    }

    /**
     * Surcharges individuelles de permissions spécifiques (Surcharge du PermissionVoter Symfony)
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id')
                    ->withPivot('is_granted')
                    ->withTimestamps();
    }

    /**
     * Rôles de l'utilisateur (Intégration personnalisée ou avec Spatie)
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    /**
     * Vérifie si l'utilisateur possède l'un des rôles spécifiés.
     */
    public function hasRole($roles): bool
    {
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }
            return false;
        }

        return $this->roles()->where('slug', $roles)->exists();
    }
}
