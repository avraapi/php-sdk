<?php

declare(strict_types=1);

namespace Avraapi\Apix\Services;

use Avraapi\Apix\Responses\ApiResponse;

/**
 * Currency Service — Multi-Currency Rates & Conversion.
 *
 * Maps to the OpenAPI `Currency` tag.
 * Endpoint prefix: /utility/currency
 *
 * All endpoints use GET requests with path parameters.
 * Rates are derived from local USD-base cross-rate computations, cached for 2 hours.
 * This is a FREE service — no wallet credits are deducted.
 *
 * @see https://avraapi.com/docs#tag/Currency
 */
class CurrencyService extends AbstractService
{
    // ── Currency Codes ───────────────────────────────────────────────────────

    /**
     * Retrieve all supported currency codes.
     *
     * Wraps: GET /utility/currency/codes
     * Provider: avraapi_multi_currency
     *
     * @return ApiResponse  The `data` array contains:
     *   - count (int)   — Total number of active currencies.
     *   - codes (array) — List of objects, each with:
     *       - code (string) — ISO 4217 currency code (e.g. 'USD', 'EUR', 'LKR').
     *       - name (string) — Human-readable currency name.
     *
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException   401
     * @throws \Avraapi\Apix\Exceptions\ApixRateLimitException        429
     * @throws \Avraapi\Apix\Exceptions\ApixException                 Catch-all.
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException          Transport failure.
     *
     * Example:
     *   $result = $apix->currency()->getCodes();
     *   echo $result->data['count'];              // 161
     *   foreach ($result->data['codes'] as $c) {
     *       echo "{$c['code']} — {$c['name']}\n"; // 'USD — United States Dollar'
     *   }
     */
    public function getCodes(): ApiResponse
    {
        /** @var ApiResponse */
        return $this->get('/utility/currency/codes');
    }

    // ── Latest Rates ─────────────────────────────────────────────────────────

    /**
     * Get all conversion rates from a given base currency.
     *
     * Wraps: GET /utility/currency/latest/{base}
     * Provider: avraapi_multi_currency
     *
     * Uses cross-rate mathematics:
     *   Rate(BASE → TARGET) = rate_to_usd(TARGET) / rate_to_usd(BASE)
     *
     * @param  string  $base  ISO 4217 base currency code (e.g. 'USD', 'EUR').
     *                        Case-insensitive — automatically uppercased.
     *
     * @return ApiResponse  The `data` array contains:
     *   - base         (string) — The base currency code used.
     *   - last_updated (string) — ISO 8601 timestamp of the last rate ingestion.
     *   - rates        (array)  — Associative array: { "EUR": 0.89, "GBP": 0.76, ... }
     *
     * @throws \Avraapi\Apix\Exceptions\ApixValidationException       400 — Invalid currency code.
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException   401
     * @throws \Avraapi\Apix\Exceptions\ApixRateLimitException        429
     * @throws \Avraapi\Apix\Exceptions\ApixException                 Catch-all.
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException          Transport failure.
     *
     * Example:
     *   $result = $apix->currency()->getLatestRates('USD');
     *   echo $result->data['rates']['EUR']; // 0.89123456
     *   echo $result->data['rates']['LKR']; // 298.50000000
     */
    public function getLatestRates(string $base): ApiResponse
    {
        $base = strtoupper(trim($base));

        /** @var ApiResponse */
        return $this->get("/utility/currency/latest/{$base}");
    }

    // ── Pair Rate ────────────────────────────────────────────────────────────

    /**
     * Get the exchange rate between two specific currencies.
     *
     * Wraps: GET /utility/currency/pair/{base}/{target}
     * Provider: avraapi_multi_currency
     *
     * @param  string  $base    Source currency code (e.g. 'USD').
     * @param  string  $target  Target currency code (e.g. 'EUR').
     *
     * @return ApiResponse  The `data` array contains:
     *   - base         (string) — Source currency code.
     *   - target       (string) — Target currency code.
     *   - rate         (float)  — Conversion rate (8 decimal places).
     *   - last_updated (string) — ISO 8601 timestamp.
     *
     * @throws \Avraapi\Apix\Exceptions\ApixValidationException       400 — Invalid currency code.
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException   401
     * @throws \Avraapi\Apix\Exceptions\ApixRateLimitException        429
     * @throws \Avraapi\Apix\Exceptions\ApixException                 Catch-all.
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException          Transport failure.
     *
     * Example:
     *   $result = $apix->currency()->getPairRate('USD', 'EUR');
     *   echo $result->data['rate']; // 0.89123456
     */
    public function getPairRate(string $base, string $target): ApiResponse
    {
        $base   = strtoupper(trim($base));
        $target = strtoupper(trim($target));

        /** @var ApiResponse */
        return $this->get("/utility/currency/pair/{$base}/{$target}");
    }

    // ── Convert ──────────────────────────────────────────────────────────────

    /**
     * Convert a specific amount from one currency to another.
     *
     * Wraps: GET /utility/currency/pair/{base}/{target}/{amount}
     * Provider: avraapi_multi_currency
     *
     * @param  string  $base    Source currency code (e.g. 'USD').
     * @param  string  $target  Target currency code (e.g. 'LKR').
     * @param  float   $amount  The amount to convert (must be > 0).
     *
     * @return ApiResponse  The `data` array contains:
     *   - base              (string) — Source currency code.
     *   - target            (string) — Target currency code.
     *   - rate              (float)  — Conversion rate used.
     *   - amount            (float)  — The original amount.
     *   - conversion_result (float)  — The converted amount (6 decimal places).
     *   - last_updated      (string) — ISO 8601 timestamp.
     *
     * @throws \Avraapi\Apix\Exceptions\ApixValidationException       400/422 — Invalid code or amount.
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException   401
     * @throws \Avraapi\Apix\Exceptions\ApixRateLimitException        429
     * @throws \Avraapi\Apix\Exceptions\ApixException                 Catch-all.
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException          Transport failure.
     *
     * Example:
     *   $result = $apix->currency()->convert('USD', 'LKR', 100.00);
     *   echo $result->data['conversion_result']; // 29850.000000
     *   echo $result->data['rate'];              // 298.50000000
     *
     *   // Display formatted:
     *   $data = $result->data;
     *   echo "{$data['amount']} {$data['base']} = {$data['conversion_result']} {$data['target']}";
     *   // "100 USD = 29850.000000 LKR"
     */
    public function convert(string $base, string $target, float $amount): ApiResponse
    {
        $base   = strtoupper(trim($base));
        $target = strtoupper(trim($target));

        /** @var ApiResponse */
        return $this->get("/utility/currency/pair/{$base}/{$target}/{$amount}");
    }
}
