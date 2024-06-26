<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutcomeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'payment_date',
        'status'
    ];

    public function outcome() {
        return $this->belongsTo(Outcome::class);
    }
}
