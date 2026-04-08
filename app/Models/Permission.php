<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $primaryKey = 'permission_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'status',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'permission_role',
            'permission_id',
            'role_id',
            'permission_id',
            'role_id'
        )->withTimestamps();
    }

    public function getIdAttribute()
    {
        return $this->attributes['permission_id'] ?? null;
    }
}
