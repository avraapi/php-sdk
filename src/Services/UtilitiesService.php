<?php

declare(strict_types=1);

namespace Avraapi\Apix\Services;

use Avraapi\Apix\Responses\ApiResponse;
use Avraapi\Apix\Responses\BinaryResponse;

/**
 * Utilities Service — QR code, barcode, and PDF generation.
 *
 * Maps to the OpenAPI `Utilities` tag.
 * Endpoint prefix: /utilities
 *
 * Response types depend on the `format` / `response_type` field:
 *   - 'png' or 'svg'  → BinaryResponse (image bytes)
 *   - 'base64'        → ApiResponse    (JSON with data_uri or base64 string)
 *   - 'binary'        → BinaryResponse (PDF bytes)
 *
 * @see https://avraapi.com/docs#tag/Utilities
 */
final class UtilitiesService extends AbstractService
{
    // ── QR Code ───────────────────────────────────────────────────────────────

    /**
     * Generate a QR code from any text, URL, or vCard payload.
     *
     * Wraps: POST /utilities/qr/generate
     * Provider: apix_qr
     *
     * @param  string       $data             Text, URL, or BEGIN:VCARD payload.
     * @param  string       $format           'png' (binary) | 'svg' (binary) | 'base64' (JSON).
     *                                        Default: 'png'.
     * @param  int|null     $size             Width and height in pixels (50–2000). Default: 300.
     * @param  string|null  $foregroundColor  6-digit hex color for dark modules (with or without #).
     * @param  string|null  $backgroundColor  6-digit hex color for background.
     * @param  string|null  $logoUrl          Publicly reachable logo URL to embed at center.
     * @param  int|null     $logoSizePercent  Logo size as % of QR image (5–40).
     * @param  bool         $privacyMode      Suppress payload storage in observability logs.
     *
     * @return ApiResponse|BinaryResponse
     *   - Returns BinaryResponse when $format is 'png' or 'svg'.
     *   - Returns ApiResponse    when $format is 'base64'.
     *     Access the data URI via: $response->data['data_uri']
     *
     * @throws \Avraapi\Apix\Exceptions\ApixValidationException
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException
     * @throws \Avraapi\Apix\Exceptions\ApixInsufficientFundsException
     * @throws \Avraapi\Apix\Exceptions\ApixRateLimitException
     * @throws \Avraapi\Apix\Exceptions\ApixException
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException
     *
     * Examples:
     *   // Binary PNG (save to disk):
     *   $response = $apix->utilities()->generateQr('https://example.com');
     *   $response->saveAs('/tmp/qr.png');
     *
     *   // Base64 for embedding in HTML:
     *   $response = $apix->utilities()->generateQr('https://example.com', format: 'base64');
     *   echo '<img src="' . $response->data['data_uri'] . '">';
     *
     *   // Styled QR with logo:
     *   $response = $apix->utilities()->generateQr(
     *       data:            'https://apix.example.com',
     *       format:          'png',
     *       size:            400,
     *       foregroundColor: '1a56db',
     *       logoUrl:         'https://example.com/logo.png',
     *       logoSizePercent: 20,
     *   );
     *   $response->saveAs('/tmp/branded-qr.png');
     */
    public function generateQr(
        string $data,
        string $format = 'png',
        ?int $size = null,
        ?string $foregroundColor = null,
        ?string $backgroundColor = null,
        ?string $logoUrl = null,
        ?int $logoSizePercent = null,
        bool $privacyMode = false,
    ): ApiResponse|BinaryResponse {
        $payload = $this->compact([
            'data'             => $data,
            'format'           => $format,
            'size'             => $size,
            'foreground_color' => $foregroundColor,
            'background_color' => $backgroundColor,
            'logo_url'         => $logoUrl,
            'logo_size_percent' => $logoSizePercent,
            'privacy_mode'     => $privacyMode ?: null, // omit if false to use server default
        ]);

        return $this->post('/utilities/qr/generate', $payload);
    }

    // ── Barcode ───────────────────────────────────────────────────────────────

    /**
     * Generate a barcode image from text or numeric data.
     *
     * Wraps: POST /utilities/barcode/generate
     * Provider: apix_barcode
     *
     * Supported symbologies (via $type):
     *   C128, C128A, C128B, C128C, EAN13, EAN8, UPCA, UPCE,
     *   C39, C39+, I25, ITF14, MSI, POSTNET
     *
     * @param  string      $data          Data string to encode (max 80 chars).
     * @param  string      $type          Barcode symbology (default: 'C128').
     * @param  string      $format        'png' or 'svg' (default: 'png').
     * @param  int|null    $height        Barcode height in pixels (20–300).
     * @param  float|null  $widthFactor   Horizontal bar width multiplier (1–4).
     * @param  bool        $privacyMode   Suppress payload storage.
     *
     * @return BinaryResponse  Always binary — barcode endpoint does not support base64.
     *
     * @throws \Avraapi\Apix\Exceptions\ApixValidationException
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException
     * @throws \Avraapi\Apix\Exceptions\ApixInsufficientFundsException
     * @throws \Avraapi\Apix\Exceptions\ApixRateLimitException
     * @throws \Avraapi\Apix\Exceptions\ApixException
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException
     *
     * Example:
     *   $response = $apix->utilities()->generateBarcode(
     *       data:   'APIX-2026-PHASE9',
     *       type:   'C128',
     *       format: 'png',
     *   );
     *   $response->saveAs('/tmp/barcode.png');
     */
    public function generateBarcode(
        string $data,
        string $type = 'C128',
        string $format = 'png',
        ?int $height = null,
        float|int|null $widthFactor = null,
        bool $privacyMode = false,
    ): BinaryResponse {
        $payload = $this->compact([
            'data'         => $data,
            'type'         => $type,
            'format'       => $format,
            'height'       => $height,
            'width_factor' => $widthFactor,
            'privacy_mode' => $privacyMode ?: null,
        ]);

        /** @var BinaryResponse $response */
        $response = $this->post('/utilities/barcode/generate', $payload);

        return $response;
    }

    // ── PDF ───────────────────────────────────────────────────────────────────

    /**
     * Convert an HTML document or fragment to a PDF file.
     *
     * Wraps: POST /utilities/pdf/generate
     * Provider: apix_html2pdf
     *
     * @param  string                                                                 $html
     *   Raw HTML content to render (max 512 KB). Full <!DOCTYPE html> documents
     *   and plain HTML fragments are both accepted.
     *
     * @param  string                                                                 $responseType
     *   'binary' — Returns BinaryResponse with raw PDF bytes (default).
     *   'base64' — Returns ApiResponse with JSON containing base64-encoded PDF.
     *
     * @param  string                                                                 $pageSize
     *   'A4' (default) | 'Letter' | 'Legal'
     *
     * @param  string                                                                 $orientation
     *   'portrait' (default) | 'landscape'
     *
     * @param  array{top?: float, right?: float, bottom?: float, left?: float}|null  $margins
     *   Custom page margins in millimetres. Keys: top, right, bottom, left.
     *
     * @return ApiResponse|BinaryResponse
     *   - BinaryResponse when $responseType is 'binary'.
     *     Save with: $response->saveAs('/tmp/invoice.pdf')
     *   - ApiResponse when $responseType is 'base64'.
     *     Access with: $response->data['data']  (base64 string)
     *     Media type:  $response->data['media_type']
     *
     * @throws \Avraapi\Apix\Exceptions\ApixValidationException
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException
     * @throws \Avraapi\Apix\Exceptions\ApixInsufficientFundsException
     * @throws \Avraapi\Apix\Exceptions\ApixRateLimitException
     * @throws \Avraapi\Apix\Exceptions\ApixException
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException
     *
     * Examples:
     *   // Binary PDF — save to disk:
     *   $html = '<h1>Invoice #001</h1><p>Total: $99.00</p>';
     *   $response = $apix->utilities()->generatePdf($html);
     *   $response->saveAs('/tmp/invoice.pdf');
     *
     *   // Landscape A4 with custom margins:
     *   $response = $apix->utilities()->generatePdf(
     *       html:        $html,
     *       pageSize:    'A4',
     *       orientation: 'landscape',
     *       margins:     ['top' => 20, 'right' => 25, 'bottom' => 20, 'left' => 25],
     *   );
     *   $response->saveAs('/tmp/landscape.pdf');
     *
     *   // Base64 response (for storage or HTTP streaming):
     *   $response = $apix->utilities()->generatePdf($html, responseType: 'base64');
     *   $base64Pdf = $response->data['data'];
     *   file_put_contents('/tmp/invoice.pdf', base64_decode($base64Pdf));
     */
    public function generatePdf(
        string $html,
        string $responseType = 'binary',
        string $pageSize = 'A4',
        string $orientation = 'portrait',
        ?array $margins = null,
    ): ApiResponse|BinaryResponse {
        $payload = $this->compact([
            'html'          => $html,
            'response_type' => $responseType,
            'page_size'     => $pageSize,
            'orientation'   => $orientation,
            'margins'       => $margins,
        ]);

        return $this->post('/utilities/pdf/generate', $payload);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Remove null values from a payload array before sending.
     *
     * This allows callers to use null as "not provided" for optional fields
     * without sending explicit null values over the wire (which can fail
     * gateway validation on `additionalProperties: false` schemas).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function compact(array $data): array
    {
        return array_filter($data, fn(mixed $value): bool => $value !== null);
    }
}
