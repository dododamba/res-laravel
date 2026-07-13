<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Permission extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'description',
        'category',
    ];

    protected static function booted()
    {
        static::creating(function (Permission $permission) {
            if (empty($permission->id)) {
                $permission->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Rôles possédant cette permission (ManyToMany)
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id');
    }

    /**
     * Utilisateurs ayant une surcharge explicite sur cette permission (pivot is_granted)
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions', 'permission_id', 'user_id')
                    ->withPivot('is_granted')
                    ->withTimestamps();
    }
}
