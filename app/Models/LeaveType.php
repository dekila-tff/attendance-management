<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $primaryKey = 'leave_type_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'code',
        'description',
        'entitlement_days',
        'is_active',
    ];

    protected $casts = [
        'entitlement_days' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get leave requests that use this leave type.
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id', 'leave_type_id');
    }

    public function getIdAttribute()
    {
        return $this->attributes['leave_type_id'] ?? null;
    }
}
