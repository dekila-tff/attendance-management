<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLeaveBalance extends Model
{
    use HasFactory;

    protected $primaryKey = 'users_leave_balance_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'max_per_year',
        'adjustment',
    ];

    protected $casts = [
        'max_per_year' => 'decimal:2',
        'adjustment' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'users_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id', 'leave_types_id');
    }

    public function getIdAttribute()
    {
        return $this->attributes['users_leave_balance_id'] ?? null;
    }
}
