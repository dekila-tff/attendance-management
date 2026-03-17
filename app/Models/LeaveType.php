<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
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
        return $this->hasMany(LeaveRequest::class);
    }
}
