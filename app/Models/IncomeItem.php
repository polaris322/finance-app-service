<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'payment_date',
        'status'
    ];

    public function income() {
        return $this->belongsTo(Income::class);
    }
}
