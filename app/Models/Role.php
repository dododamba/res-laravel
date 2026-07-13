<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Role extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
    ];

    protected static function booted()
    {
        static::creating(function (Role $role) {
            if (empty($role->id)) {
                $role->id = (string) Str::uuid();
            }
            if (empty($role->slug) && !empty($role->name)) {
                $role->slug = Str::slug($role->name);
            }
        });
    }

    /**
     * Rôles associés aux utilisateurs (ManyToMany via table Pivot)
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
    }

    /**
     * Permissions attribuées à ce Rôle (ManyToMany)
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }
}
