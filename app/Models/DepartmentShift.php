<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'department',
        'name',
        'start_time',
        'end_time',
        'on_time_until',
        'clock_out_after',
        'is_overnight',
        'is_active',
    ];

    protected $casts = [
        'is_overnight' => 'boolean',
        'is_active' => 'boolean',
    ];
}
