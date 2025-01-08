<?php

namespace App\Enums;

enum TrackerStatus
{
    case PENDING = 'PENDING';
    case IN_TRANSIT = 'IN_TRANSIT';
    case DELIVERED = 'DELIVERED';
    case EXCEPTION = 'EXCEPTION';
}
