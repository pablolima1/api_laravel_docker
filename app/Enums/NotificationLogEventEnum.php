<?php

namespace App\Enums;

enum NotificationLogEventEnum: string
{
    case SendFailed = 'notification.send_failed';
    case RetriesExhausted = 'notification.retries_exhausted';
}