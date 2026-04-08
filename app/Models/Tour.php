<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    use HasFactory;

    protected $table = 'tour';

    protected $primaryKey = 'tour_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'users_id',
        'department_id',
        'place',
        'start_date',
        'end_date',
        'purpose',
        'office_order_pdf',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id', 'user_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }
}
