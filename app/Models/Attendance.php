<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    const CREATED_AT = null; // Disable created_at timestamp

    protected $fillable = [
        'user_id',
        'date',
        'shift_name',
        'shift_on_time_until',
        'shift_clock_out_after',
        'shift_is_overnight',
        'clock_in',
        'clock_out',
        'clockIn_address',
        'clockOut_address',
        'status',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime:H:i:s',
        'clock_out' => 'datetime:H:i:s',
        'shift_is_overnight' => 'boolean',
    ];

    /**
     * Get the user that owns the attendance.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'users_id');
    }
}
