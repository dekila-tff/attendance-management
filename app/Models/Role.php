<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $primaryKey = 'role_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = ['name', 'description', 'status'];

    protected $casts = [
        'role_id' => 'integer',
    ];

    /**
     * Get the users that have this role.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_role',
            'role_id',
            'permission_id',
            'role_id',
            'permission_id'
        )->withTimestamps();
    }

    public function getIdAttribute()
    {
        return $this->attributes['role_id'] ?? null;
    }
}
