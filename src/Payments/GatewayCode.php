<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

enum GatewayCode: string
{
    case PayHere = 'payhere';
    case MarxPay = 'marxpay';
    case DirectPay = 'directpay';
    case PayPlus = 'payplus';
    case WebXPay = 'webxpay';
    case Koko = 'koko';
    case OnePay = 'onepay';
    case Stripe = 'stripe';
}
