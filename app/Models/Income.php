<?php

namespace App\Models;

use App\Enum\FrequencyEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\TypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'name',
        'payment_method',
        'frequency',
        'amount',
        'start_date',
        'end_date'
    ];

    protected $casts = [
        'type' => TypeEnum::class,
        'payment_method' => PaymentMethodEnum::class,
        'frequency' => FrequencyEnum::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(IncomeItem::class);
    }
}
