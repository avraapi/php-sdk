<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

/** Public, non-secret reasons that checkout is presently unavailable. */
enum PaymentAvailabilityReason: string
{
    case UpgEntitlementInactive = 'upg_entitlement_inactive';
    case UpgGatewayNotEntitled = 'upg_gateway_not_entitled';
    case PaymentConfigurationNotAvailable = 'payment_configuration_not_available';
    case ProjectPaused = 'project_paused';
    case Unknown = 'unknown';

    public static function fromApi(mixed $value): ?self
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return self::tryFrom($value) ?? self::Unknown;
    }
}
