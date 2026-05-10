<?php

declare(strict_types=1);

namespace Avraapi\Apix\Services;

use Avraapi\Apix\Responses\ApiResponse;

/**
 * Security Service — VPN & Proxy Shield, Burner Email Detection.
 *
 * Maps to the OpenAPI `Security` tag.
 * Endpoint prefix: /security
 *
 * All methods return an ApiResponse wrapping the standard AvraAPI envelope:
 *   { "success": true, "request_id": "...", "data": { ... } }
 *
 * @see https://avraapi.com/docs#tag/Security
 */
class SecurityService extends AbstractService
{
    // ── VPN & Proxy Shield ────────────────────────────────────────────────────

    /**
     * Check whether an IP address is associated with a VPN, proxy, Tor node,
     * relay (iCloud Private Relay), or hosting/datacenter.
     *
     * Wraps: POST /security/vpn-shield
     * Provider: avraapi_vpn_shield
     *
     * The API uses a multi-provider key-pooling strategy (vpnapi.io + iplocate.io)
     * with 24-hour caching per IP. Results are normalized into a unified response.
     *
     * @param  string  $ip  A valid IPv4 or IPv6 address to analyse.
     *
     * @return ApiResponse  The `data` array contains:
     *   - ip_address    (string)  — The IP that was analysed.
     *   - is_vpn        (bool)    — Detected as a VPN endpoint.
     *   - is_proxy      (bool)    — Detected as a proxy server.
     *   - is_tor        (bool)    — Detected as a Tor exit node.
     *   - is_relay      (bool)    — Detected as an iCloud Private Relay node.
     *   - is_hosting    (bool)    — Detected as a hosting/datacenter IP.
     *   - country_code  (?string) — ISO 3166-1 alpha-2 country code.
     *   - city          (?string) — City name (may be null).
     *   - asn           (?string) — Autonomous System Number.
     *   - network_name  (?string) — ISP / network organization name.
     *   - provider_name (string)  — Upstream provider that answered ('vpnapi' or 'iplocate').
     *
     * @throws \Avraapi\Apix\Exceptions\ApixValidationException       422 — Invalid IP format.
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException   401 — Bad credentials.
     * @throws \Avraapi\Apix\Exceptions\ApixInsufficientFundsException 402 — Wallet depleted.
     * @throws \Avraapi\Apix\Exceptions\ApixRateLimitException        429 — Rate limit exceeded.
     * @throws \Avraapi\Apix\Exceptions\ApixServiceUnavailableException 503 — All upstream providers exhausted.
     * @throws \Avraapi\Apix\Exceptions\ApixException                 Catch-all.
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException          Transport failure.
     *
     * Example:
     *   $result = $apix->security()->checkVpn('8.8.8.8');
     *   echo $result->data['is_vpn'];        // false
     *   echo $result->data['country_code'];   // 'US'
     *   echo $result->data['network_name'];   // 'Google LLC'
     *
     *   // Check multiple threat flags at once:
     *   $data = $result->data;
     *   $isThreat = $data['is_vpn'] || $data['is_proxy'] || $data['is_tor'];
     */
    public function checkVpn(string $ip): ApiResponse
    {
        /** @var ApiResponse */
        return $this->post('/security/vpn-shield', ['ip' => $ip]);
    }

    // ── Burner Email Shield ──────────────────────────────────────────────────

    /**
     * Check whether an email address uses a temporary/disposable email domain.
     *
     * Wraps: POST /security/burner-email-shield
     * Provider: avraapi_burner_email_shield
     *
     * The API performs a dual-list Redis SISMEMBER lookup against both a custom
     * blocklist and a curated global blocklist (7,000+ disposable domains).
     * Sub-millisecond response times are typical.
     *
     * @param  string  $email  A valid email address to check (max 255 chars, RFC 5322).
     *
     * @return ApiResponse  The `data` array contains:
     *   - email             (string) — The email that was checked.
     *   - domain            (string) — Extracted domain portion (lowercase).
     *   - is_valid_syntax   (bool)   — Whether the email passes RFC 5322 validation.
     *   - is_disposable     (bool)   — Whether the domain is in the blocklist.
     *   - source            (string) — Which list matched: 'custom', 'global', or 'none'.
     *   - execution_time_ms (float)  — Wall-clock time in milliseconds (2 decimal places).
     *
     * @throws \Avraapi\Apix\Exceptions\ApixValidationException       422 — Invalid email format.
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException   401 — Bad credentials.
     * @throws \Avraapi\Apix\Exceptions\ApixInsufficientFundsException 402 — Wallet depleted.
     * @throws \Avraapi\Apix\Exceptions\ApixRateLimitException        429 — Rate limit exceeded.
     * @throws \Avraapi\Apix\Exceptions\ApixException                 Catch-all.
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException          Transport failure.
     *
     * Example:
     *   $result = $apix->security()->checkBurnerEmail('user@mailinator.com');
     *   echo $result->data['is_disposable'];     // true
     *   echo $result->data['source'];            // 'global'
     *   echo $result->data['execution_time_ms']; // 0.42
     *
     *   // Guard a registration form:
     *   if ($result->data['is_disposable']) {
     *       throw new \Exception('Disposable emails are not allowed.');
     *   }
     */
    public function checkBurnerEmail(string $email): ApiResponse
    {
        /** @var ApiResponse */
        return $this->post('/security/burner-email-shield', ['email' => $email]);
    }
}
