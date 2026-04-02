<?php

declare(strict_types=1);

namespace Avraapi\Apix;

/**
 * Immutable configuration container for the APIX PHP SDK.
 *
 * Resolution priority (highest → lowest):
 *   1. Values passed explicitly via the $config array in the constructor.
 *   2. Environment variables read via getenv() / $_ENV / $_SERVER.
 *
 * Required keys:
 *   APIX_PROJECT_KEY   — Your project client ID (X-API-KEY header)
 *   APIX_API_SECRET    — Your project API secret (X-API-SECRET header)
 *
 * Optional keys:
 *   APIX_ENV           — 'dev' | 'prod' (default: 'dev')
 *   APIX_BASE_URL      — Override base URL for local testing
 *                        (default: 'https://avraapi.com/api/v1')
 *   APIX_TIMEOUT       — HTTP request timeout in seconds (default: 30)
 *   APIX_CONNECT_TIMEOUT — TCP connect timeout in seconds (default: 10)
 */
final class Config
{
    /** Production base URL of the APIX gateway. */
    public const DEFAULT_BASE_URL = 'https://avraapi.com/api/v1';

    /** Default HTTP request timeout in seconds. */
    public const DEFAULT_TIMEOUT = 30;

    /** Default TCP connection timeout in seconds. */
    public const DEFAULT_CONNECT_TIMEOUT = 10;

    public readonly string $projectKey;
    public readonly string $apiSecret;
    public readonly string $env;
    public readonly string $baseUrl;
    public readonly int    $timeout;
    public readonly int    $connectTimeout;

    /**
     * @param  array<string, mixed>  $config  Explicit overrides (highest priority).
     *
     * @throws \InvalidArgumentException When APIX_PROJECT_KEY or APIX_API_SECRET cannot be resolved.
     */
    public function __construct(array $config = [])
    {
        $this->projectKey = $this->resolve('APIX_PROJECT_KEY', $config)
            ?? throw new \InvalidArgumentException(
                'APIX_PROJECT_KEY is required. Pass it in the $config array or set the APIX_PROJECT_KEY environment variable.'
            );

        $this->apiSecret = $this->resolve('APIX_API_SECRET', $config)
            ?? throw new \InvalidArgumentException(
                'APIX_API_SECRET is required. Pass it in the $config array or set the APIX_API_SECRET environment variable.'
            );

        $this->env = $this->normalizeEnv(
            $this->resolve('APIX_ENV', $config) ?? 'dev'
        );

        $this->baseUrl = rtrim(
            $this->resolve('APIX_BASE_URL', $config) ?? self::DEFAULT_BASE_URL,
            '/'
        );

        $timeout = $this->resolve('APIX_TIMEOUT', $config);
        $this->timeout = $timeout !== null ? (int) $timeout : self::DEFAULT_TIMEOUT;

        $connectTimeout = $this->resolve('APIX_CONNECT_TIMEOUT', $config);
        $this->connectTimeout = $connectTimeout !== null ? (int) $connectTimeout : self::DEFAULT_CONNECT_TIMEOUT;
    }

    /**
     * Resolve a configuration key from the explicit array, then getenv().
     *
     * @param  array<string, mixed>  $config
     */
    private function resolve(string $key, array $config): ?string
    {
        // 1. Explicit array (may use SCREAMING_SNAKE or camelCase key)
        if (isset($config[$key]) && is_string($config[$key]) && $config[$key] !== '') {
            return $config[$key];
        }

        // 2. getenv() — works with dotenv, Docker, and native server env vars
        $env = getenv($key);
        if (is_string($env) && $env !== '') {
            return $env;
        }

        // 3. $_ENV superglobal (set by some SAPI configurations)
        if (isset($_ENV[$key]) && is_string($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        return null;
    }

    /**
     * Normalize environment shorthand aliases.
     *
     * Mirrors the normalizeEnvCode() logic in APIX's CheckApiKey middleware
     * so the SDK and server always agree on the target environment.
     *
     * Accepted values → header sent:
     *   'dev' / 'development' → 'dev'
     *   'prod' / 'production' → 'prod'
     *   anything else         → 'dev' (safe fallback)
     */
    private function normalizeEnv(string $raw): string
    {
        return match (strtolower(trim($raw))) {
            'prod', 'production' => 'prod',
            default              => 'dev',
        };
    }
}
