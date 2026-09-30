<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

enum PaymentStatus: string
{
    case Succeeded = 'succeeded';
    case Pending = 'pending';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Unknown = 'unknown';
}
