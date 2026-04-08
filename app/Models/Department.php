<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $primaryKey = 'department_id';

    protected $fillable = [
        'name',
        'hod_user_id',
        'status',
    ];

    public function hod()
    {
        return $this->belongsTo(User::class, 'hod_user_id');
    }

    public function getIdAttribute()
    {
        return $this->attributes['department_id'] ?? null;
    }
}
