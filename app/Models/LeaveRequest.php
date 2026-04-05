<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'leave_request_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'leave_type',
        'is_direct_to_ms',
        'submit_to',
        'start_date',
        'end_date',
        'total_days',
        'balance',
        'reason',
        'rejection_reason',
        'prescription',
        'hod_status',
        'ms_status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_direct_to_ms' => 'boolean',
        'total_days' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    /**
     * Get the user that owns the leave request.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'users_id');
    }

    /**
     * Get the leave type for this leave request.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id', 'leave_types_id');
    }

    public function getIdAttribute()
    {
        return $this->attributes['leave_request_id'] ?? null;
    }
}
