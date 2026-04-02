<?php

declare(strict_types=1);

namespace Avraapi\Apix;

use Avraapi\Apix\Responses\ApiResponse;
use Avraapi\Apix\Responses\BinaryResponse;
use Avraapi\Apix\Services\LocationService;
use Avraapi\Apix\Services\SmsService;
use Avraapi\Apix\Services\UtilitiesService;

/**
 * APIX PHP SDK — Main Client
 *
 * The primary entry point for all APIX API operations. Instantiate once and
 * reuse across your application (safe for dependency-injection containers).
 *
 * ── Quick Start ───────────────────────────────────────────────────────────────
 *
 *   $apix = new ApixClient([
 *       'APIX_PROJECT_KEY' => 'your-project-key',
 *       'APIX_API_SECRET'  => 'your-api-secret',
 *       'APIX_ENV'         => 'dev',  // 'dev' or 'prod'
 *   ]);
 *
 *   // Or rely on environment variables:
 *   $apix = new ApixClient(); // reads APIX_PROJECT_KEY, APIX_API_SECRET from getenv()
 *
 * ── Service Groups ────────────────────────────────────────────────────────────
 *
 *   $apix->location()   — IP geolocation lookup
 *   $apix->sms()        — SMS send / balance operations
 *   $apix->utilities()  — QR codes, barcodes, PDF generation
 *
 * ── Provider Override ─────────────────────────────────────────────────────────
 *
 *   $apix->sms()->withProvider('quicksend')->sendSingle('0771234567', 'Hello!');
 *
 * ── Universal Call ────────────────────────────────────────────────────────────
 *
 *   // For endpoints not yet in the SDK:
 *   $response = $apix->call('POST', 'new-sector/new-action', ['key' => 'value']);
 *
 *   // Smart path normalization — all equivalent:
 *   $apix->call('POST', 'sms/send', $payload);
 *   $apix->call('POST', '/sms/send', $payload);
 *   $apix->call('POST', '/api/v1/sms/send', $payload);
 *   $apix->call('POST', 'https://avraapi.com/api/v1/sms/send', $payload);
 *
 * @api
 */
final class ApixClient
{
    public readonly Config $config;

    private readonly HttpClient $http;

    // ── Lazily instantiated service instances ─────────────────────────────────
    private ?LocationService  $locationService  = null;
    private ?SmsService       $smsService       = null;
    private ?UtilitiesService $utilitiesService = null;

    /**
     * Create a new APIX client.
     *
     * @param  array<string, mixed>  $config  Explicit configuration overrides.
     *                                        Falls back to environment variables when omitted.
     *
     * @throws \InvalidArgumentException When required credentials cannot be resolved.
     *
     * Example with explicit config:
     *   $apix = new ApixClient([
     *       'APIX_PROJECT_KEY' => 'pk_live_...',
     *       'APIX_API_SECRET'  => 'sk_live_...',
     *       'APIX_ENV'         => 'prod',
     *   ]);
     *
     * Example using environment variables (.env or system env):
     *   // Set: APIX_PROJECT_KEY, APIX_API_SECRET, APIX_ENV
     *   $apix = new ApixClient();
     *
     * Example for local development against Laravel Sail:
     *   $apix = new ApixClient([
     *       'APIX_PROJECT_KEY' => 'pk_dev_...',
     *       'APIX_API_SECRET'  => 'sk_dev_...',
     *       'APIX_ENV'         => 'dev',
     *       'APIX_BASE_URL'    => 'http://localhost/api/v1',
     *   ]);
     */
    public function __construct(array $config = [])
    {
        $this->config = new Config($config);
        $this->http   = new HttpClient($this->config);
    }

    // ── Service accessors (lazy-init, fluent) ─────────────────────────────────

    /**
     * Access the Location service group.
     *
     * Available operations:
     *   lookupIp(string $ip): ApiResponse
     *
     * Example:
     *   $geo = $apix->location()->lookupIp('112.134.205.126');
     *   echo $geo->data['country']; // 'Sri Lanka'
     */
    public function location(): LocationService
    {
        return $this->locationService ??= new LocationService($this->http);
    }

    /**
     * Access the SMS service group.
     *
     * Available operations:
     *   sendSingle(string $to, string $message): ApiResponse
     *   sendBulkSame(array $recipients, string $message, bool $checkCost): ApiResponse
     *   sendBulkDifferent(array $messages): ApiResponse
     *   getBalance(): ApiResponse
     *
     * Example:
     *   $apix->sms()->sendSingle('0771234567', 'Hello from APIX!');
     */
    public function sms(): SmsService
    {
        return $this->smsService ??= new SmsService($this->http);
    }

    /**
     * Access the Utilities service group.
     *
     * Available operations:
     *   generateQr(string $data, ...): ApiResponse|BinaryResponse
     *   generateBarcode(string $data, ...): BinaryResponse
     *   generatePdf(string $html, ...): ApiResponse|BinaryResponse
     *
     * Example:
     *   $apix->utilities()->generatePdf('<h1>Invoice</h1>')->saveAs('/tmp/inv.pdf');
     */
    public function utilities(): UtilitiesService
    {
        return $this->utilitiesService ??= new UtilitiesService($this->http);
    }

    // ── Universal Call ────────────────────────────────────────────────────────

    /**
     * Make a raw API call to any APIX endpoint.
     *
     * This is the escape hatch for endpoints not yet covered by a typed service
     * method. The path is normalized using the same smart algorithm used by all
     * service methods — so any path format works.
     *
     * Supported methods: 'POST' (APIX gateway uses POST for all operations).
     * Pass other methods for future-proofing; only POST is currently dispatched.
     *
     * @param  string                $method   HTTP method (case-insensitive, e.g. 'POST').
     * @param  string                $path     Endpoint path in any of the supported formats:
     *                                           - 'sms/send'
     *                                           - '/sms/send'
     *                                           - '/api/v1/sms/send'
     *                                           - 'https://avraapi.com/api/v1/sms/send'
     * @param  array<string, mixed>  $payload  JSON-serializable request body.
     *
     * @return ApiResponse|BinaryResponse
     *
     * @throws \Avraapi\Apix\Exceptions\ApixException        On any API-level error.
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException On transport failure.
     * @throws \InvalidArgumentException                     On unsupported HTTP method.
     *
     * Example:
     *   $response = $apix->call('POST', 'payments/checkout', [
     *       'amount'   => 2500,
     *       'currency' => 'LKR',
     *   ]);
     *   echo $response->data['checkout_url'];
     */
    public function call(
        string $method,
        string $path,
        array $payload = [],
    ): ApiResponse|BinaryResponse {
        return match (strtoupper(trim($method))) {
            'POST' => $this->http->post($path, $payload),
            default => throw new \InvalidArgumentException(
                "APIX SDK: Unsupported HTTP method '{$method}'. The APIX gateway uses POST for all operations."
            ),
        };
    }
}
