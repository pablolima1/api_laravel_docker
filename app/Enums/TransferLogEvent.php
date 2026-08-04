<?php

namespace App\Enums;

enum TransferLogEvent: string
{
    case Started = 'transfer.started';
    case MerchantCannotSend = 'transfer.failed.merchant_cannot_send';
    case InsufficientBalance = 'transfer.failed.insufficient_balance';
    case NotAuthorized = 'transfer.failed.not_authorized';
    case Completed = 'transfer.completed';
}
