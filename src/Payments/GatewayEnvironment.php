<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

/**
 * Selects the merchant's vaulted provider credential profile.
 *
 * This is deliberately independent from APIX_ENV, which authenticates the
 * AvraAPI project client itself. Environment configuration is server-only.
 */
enum GatewayEnvironment: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';

    public static function configuredFor(GatewayCode $gateway, ?self $explicit = null): ?self
    {
        if ($explicit !== null) {
            return $explicit;
        }

        $override = self::configuredOverride($gateway);
        if ($override !== null) {
            return $override;
        }

        return self::configuredDefault();
    }

    private static function configuredDefault(): ?self
    {
        $configured = getenv('APIX_PAYMENT_GATEWAY_ENV');

        return is_string($configured) ? self::normalize($configured) : null;
    }

    private static function configuredOverride(GatewayCode $gateway): ?self
    {
        $configured = getenv('APIX_PAYMENT_GATEWAY_ENV_OVERRIDES');
        if (! is_string($configured) || trim($configured) === '') {
            return null;
        }

        $selected = null;
        foreach (explode(',', $configured) as $definition) {
            $parts = explode(':', trim($definition), 2);
            if (count($parts) !== 2 || trim($parts[0]) === '' || trim($parts[1]) === '') {
                throw new \InvalidArgumentException('APIX_PAYMENT_GATEWAY_ENV_OVERRIDES must use gateway:sandbox or gateway:production entries.');
            }
            if (strtolower(trim($parts[0])) !== $gateway->value) {
                continue;
            }
            if ($selected !== null) {
                throw new \InvalidArgumentException("APIX_PAYMENT_GATEWAY_ENV_OVERRIDES contains more than one entry for {$gateway->value}.");
            }

            $selected = self::normalize($parts[1]);
            if ($selected === null) {
                throw new \InvalidArgumentException("APIX_PAYMENT_GATEWAY_ENV_OVERRIDES has an invalid environment for {$gateway->value}.");
            }
        }

        return $selected;
    }

    private static function normalize(string $value): ?self
    {
        return match (strtolower(trim($value))) {
            'sandbox' => self::Sandbox,
            'production' => self::Production,
            default => null,
        };
    }
}
