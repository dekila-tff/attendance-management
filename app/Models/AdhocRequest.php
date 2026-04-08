<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdhocRequest extends Model
{
    use HasFactory;

    protected $table = 'adhoc_requests';

    protected $primaryKey = 'adhoc_request_id';

    public $incrementing = true;

    protected $keyType = 'int';

    const CREATED_AT = null;

    protected $fillable = [
        'user_id',
        'name',
        'date',
        'purpose',
        'remark',
        'updated_at',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
