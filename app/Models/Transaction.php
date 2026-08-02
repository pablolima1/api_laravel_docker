<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'uuid',
        'payer_id',
        'payee_id',
        'amount',
        'transaction_status_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'payer_id');
    }

    public function payee(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'payee_id');
    }
}
