<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

enum CheckoutMode: string
{
    case Redirect = 'redirect';
    case Overlay = 'overlay';
    /** @deprecated Since v1.0.0, use Redirect. APIX normalizes this alias to redirect. */
    case HostedSession = 'hosted_session';
    /** DirectPay's official SDK renders its checkout inside a merchant container. */
    case Embedded = 'embedded';
}
