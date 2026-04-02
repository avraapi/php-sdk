<?php

declare(strict_types=1);

namespace Avraapi\Apix\Services;

use Avraapi\Apix\Responses\ApiResponse;

/**
 * Location Service — IP intelligence and geolocation lookups.
 *
 * Maps to the OpenAPI `Location` tag.
 * Endpoint prefix: /location
 *
 * @see https://avraapi.com/docs#tag/Location
 */
final class LocationService extends AbstractService
{
    // ── Endpoints ─────────────────────────────────────────────────────────────

    /**
     * Resolve an IP address to geographic and ISP metadata.
     *
     * Wraps: POST /location/lookup
     * Provider: MaxMind GeoLite2 (code: 'maxmind')
     *
     * @param  string  $ip           IPv4 or IPv6 address to resolve.
     * @param  bool    $privacyMode  When true, the gateway suppresses payload
     *                               storage in observability logs (X-Privacy-Mode: 1).
     *
     * @return ApiResponse  Success envelope. Key fields:
     *   $response->data['country']       — e.g. 'Sri Lanka'
     *   $response->data['country_code']  — e.g. 'LK'
     *   $response->data['city']          — nullable string
     *   $response->data['isp']           — nullable string
     *   $response->data['latitude']      — float
     *   $response->data['longitude']     — float
     *   $response->data['timezone']      — e.g. 'Asia/Colombo'
     *
     * @throws \Avraapi\Apix\Exceptions\ApixValidationException      On invalid IP format.
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException  On auth failure.
     * @throws \Avraapi\Apix\Exceptions\ApixInsufficientFundsException When credits are exhausted.
     * @throws \Avraapi\Apix\Exceptions\ApixException                On any other API error.
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException         On transport failure.
     *
     * Example:
     *   $response = $apix->location()->lookupIp('112.134.205.126');
     *   echo $response->data['country'];  // 'Sri Lanka'
     *
     *   // Force a specific provider:
     *   $response = $apix->location()->withProvider('maxmind')->lookupIp('1.1.1.1');
     */
    public function lookupIp(string $ip, bool $privacyMode = false): ApiResponse
    {
        $headers = $privacyMode ? ['X-Privacy-Mode' => '1'] : [];

        /** @var ApiResponse $response */
        $response = $this->post('/location/lookup', ['ip' => $ip], $headers);

        return $response;
    }
}
