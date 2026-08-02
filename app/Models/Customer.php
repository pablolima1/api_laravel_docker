<?php

namespace App\Models;

use App\Enums\CustomerTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    public function type(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function canSendMoney(): bool
    {
        return CustomerTypeEnum::from($this->type->name)->canSendMoney();
    }
}
