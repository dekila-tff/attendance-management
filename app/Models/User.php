<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'eid',
        'designation',
        'department',
        'role_id',
        'status',
        'profile_picture',
        'device_id',
        'verification_code',
        'verification_code_expires_at',
        'out_of_station',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role_id' => 'integer',
        'verification_code_expires_at' => 'datetime',
    ];

    /**
     * Get the attendances for the user.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id', 'user_id');
    }

    /**
     * Get the leave requests for the user.
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'user_id', 'user_id');
    }

    /**
     * Get the role that the user belongs to.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    /**
     * Get role name from role_id
     * 1 = MS, 2 = HoD, 3 = Employee
     */
    public function getRoleNameAttribute()
    {
        return $this->role?->name ?? 'Employee';
    }

    public function getIdAttribute()
    {
        return $this->attributes['user_id'] ?? null;
    }

    public function getUsersIdAttribute()
    {
        return $this->attributes['user_id'] ?? null;
    }
}
