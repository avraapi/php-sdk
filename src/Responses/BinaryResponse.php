<?php

declare(strict_types=1);

namespace Avraapi\Apix\Responses;

/**
 * Strongly-typed wrapper for a binary APIX response.
 *
 * Returned when the gateway sends raw binary content:
 *   - QR code  → image/png  or  image/svg+xml
 *   - Barcode  → image/png  or  image/svg+xml
 *   - PDF      → application/pdf
 *
 * Usage:
 *   $response = $apix->utilities()->generateQr(data: 'https://example.com');
 *   $response->saveAs('/tmp/qr.png');
 *
 *   // Or stream it directly in a web context:
 *   header('Content-Type: ' . $response->contentType);
 *   echo $response->body;
 */
final class BinaryResponse
{
    /**
     * Raw binary content received from the gateway.
     */
    public readonly string $body;

    /**
     * MIME type of the content (e.g. 'image/png', 'application/pdf').
     */
    public readonly string $contentType;

    /**
     * Size of the body in bytes.
     */
    public readonly int $size;

    /**
     * HTTP status code of the underlying response.
     */
    public readonly int $httpStatus;

    /**
     * X-APIX-Request-ID response header value, if present.
     */
    public readonly ?string $requestId;

    /**
     * @param  string   $body         Raw binary content.
     * @param  string   $contentType  MIME type from Content-Type header.
     * @param  int      $httpStatus   HTTP response status code.
     * @param  ?string  $requestId    Value of X-APIX-Request-ID header, if set.
     */
    public function __construct(
        string $body,
        string $contentType,
        int $httpStatus = 200,
        ?string $requestId = null,
    ) {
        $this->body        = $body;
        $this->contentType = $contentType;
        $this->httpStatus  = $httpStatus;
        $this->requestId   = $requestId;
        $this->size        = strlen($body);
    }

    /**
     * Save the binary content to a file on disk.
     *
     * Creates intermediate directories if they do not exist.
     *
     * @param  string  $path  Absolute or relative filesystem path.
     *
     * @throws \RuntimeException When the directory cannot be created or the file cannot be written.
     *
     * @return string  The resolved absolute path of the saved file.
     */
    public function saveAs(string $path): string
    {
        $absolutePath = $path;

        // Resolve relative paths against the current working directory
        if (!str_starts_with($path, '/') && !preg_match('/^[a-zA-Z]:[\/\\\\]/', $path)) {
            $absolutePath = rtrim((string) getcwd(), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . ltrim($path, DIRECTORY_SEPARATOR);
        }

        $directory = dirname($absolutePath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException(
                    "APIX SDK: Failed to create directory '{$directory}'. Check filesystem permissions."
                );
            }
        }

        $written = file_put_contents($absolutePath, $this->body);

        if ($written === false) {
            throw new \RuntimeException(
                "APIX SDK: Failed to write binary content to '{$absolutePath}'. Check filesystem permissions."
            );
        }

        return $absolutePath;
    }

    /**
     * Whether this response contains a PDF document.
     */
    public function isPdf(): bool
    {
        return str_contains($this->contentType, 'application/pdf');
    }

    /**
     * Whether this response contains a PNG image.
     */
    public function isPng(): bool
    {
        return str_contains($this->contentType, 'image/png');
    }

    /**
     * Whether this response contains an SVG image.
     */
    public function isSvg(): bool
    {
        return str_contains($this->contentType, 'image/svg');
    }

    /**
     * Return a data URI (base64-encoded) suitable for embedding in HTML.
     *
     * Example: <img src="<?= $response->toDataUri() ?>">
     */
    public function toDataUri(): string
    {
        return sprintf(
            'data:%s;base64,%s',
            $this->contentType,
            base64_encode($this->body),
        );
    }
}
