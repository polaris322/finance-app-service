<?php

namespace App\Models;

use App\Enum\CategoryEnum;
use App\Enum\FrequencyEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\StatusEnum;
use App\Enum\TypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outcome extends Model
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
        'category',
        'amount',
        'cuotas',
        'attachment',
        'note',
        'start_date',
        'end_date',
        'status'
    ];

    protected $casts = [
        'type' => TypeEnum::class,
        'payment_method' => PaymentMethodEnum::class,
        'frequency' => FrequencyEnum::class,
        'category' => CategoryEnum::class,
        'status' => StatusEnum::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items() {
        return $this->hasMany(OutcomeItem::class);
    }
}
