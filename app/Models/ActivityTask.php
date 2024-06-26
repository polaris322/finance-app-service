<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityTask extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'payment_method',
        'amount',
        'status',
        'start_date',
        'end_date'
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
