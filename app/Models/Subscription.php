<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'description',
        'amount',
        'due_day',
        'category',
        'is_indefinite',
        'duration_months',
        'start_date',
        'status',
    ];

    protected $casts = [
        'is_indefinite' => 'boolean',
        'start_date' => 'date',
        'due_day' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
