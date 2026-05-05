# AvraAPI PHP SDK

Official PHP SDK for the [AvraAPI (APIX)](https://avraapi.com) enterprise API gateway.

Zero Laravel dependencies — works in any PHP 8.2+ project.

```bash
composer require avraapi/php-sdk
```

## Quick Start

```php
use Avraapi\Apix\ApixClient;

$apix = new ApixClient([
    'apiKey'    => 'your-api-key',
    'apiSecret' => 'your-api-secret',
    'env'       => 'dev', // 'dev' or 'prod'
]);
```

## Services

| Service | Accessor | Endpoints |
|---------|----------|-----------|
| Location | `$apix->location()` | IP geolocation lookups |
| SMS | `$apix->sms()` | Single, bulk-same, bulk-different, balance |
| Utilities | `$apix->utilities()` | QR codes, barcodes, **PDF generation** |

---

## PDF Generation

### Basic HTML to PDF

```php
$html = '<h1>Invoice #001</h1><p>Total: $99.00</p>';
$response = $apix->utilities()->generatePdf($html);
$response->saveAs('/tmp/invoice.pdf');
```

### Landscape with Custom Margins

```php
$response = $apix->utilities()->generatePdf(
    html:        $html,
    pageSize:    'A4',
    orientation: 'landscape',
    margins:     ['top' => 20, 'right' => 25, 'bottom' => 20, 'left' => 25],
);
$response->saveAs('/tmp/landscape.pdf');
```

### Base64 JSON Response

```php
$response = $apix->utilities()->generatePdf($html, responseType: 'base64');
$base64Pdf = $response->data['data'];
file_put_contents('/tmp/invoice.pdf', base64_decode($base64Pdf));
```

---

## Generating PDFs from Complex Templates (Base64 Mode)

When your HTML contains quotes, newlines, inline CSS, or special characters, JSON escaping can cause issues. **Base64 mode** solves this by encoding the HTML before transport.

### Option A: Use the `generatePdfFromBase64()` Helper (Recommended)

The helper accepts **raw HTML** and encodes it automatically:

```php
// Load a complex template from disk
$html = file_get_contents('/templates/invoice.html');

// The SDK Base64-encodes internally — no manual encoding needed
$response = $apix->utilities()->generatePdfFromBase64($html);
$response->saveAs('/tmp/invoice.pdf');

// With full options:
$response = $apix->utilities()->generatePdfFromBase64(
    html:        $html,
    responseType: 'binary',
    pageSize:    'Letter',
    orientation: 'landscape',
    margins:     ['top' => 15, 'right' => 20, 'bottom' => 15, 'left' => 20],
    privacyMode: true,
);
$response->saveAs('/tmp/invoice.pdf');
```

### Option B: Manual Base64 Encoding

If you need full control, encode the HTML yourself and set `isBase64: true`:

```php
$html = file_get_contents('/templates/invoice.html');

$response = $apix->utilities()->generatePdf(
    html:     base64_encode($html),
    isBase64: true,
);
$response->saveAs('/tmp/invoice.pdf');
```

### How It Works

1. The SDK sends the Base64 string in the `html` field with `is_base64: true`.
2. The server decodes the Base64 content before validation and rendering.
3. The **512 KB size limit** applies to the **decoded** HTML, not the encoded payload.

---

## Saving Files to Disk

Both `BinaryResponse` and the base64 JSON response support saving to disk:

```php
// Binary response — use saveAs() directly:
$response = $apix->utilities()->generatePdf($html);
$savedPath = $response->saveAs('/tmp/invoice.pdf');
echo "Saved to: {$savedPath}";

// BinaryResponse also provides:
$response->body;         // Raw binary string
$response->contentType;  // 'application/pdf'
$response->size;         // Size in bytes
$response->isPdf();      // true
$response->toDataUri();  // 'data:application/pdf;base64,...'

// Base64 JSON response — decode and write manually:
$response = $apix->utilities()->generatePdf($html, responseType: 'base64');
file_put_contents('/tmp/invoice.pdf', base64_decode($response->data['data']));
```

---

## Privacy Mode

For sensitive documents (invoices, contracts, PII), enable privacy mode to exclude HTML content from observability logs:

```php
$response = $apix->utilities()->generatePdf($html, privacyMode: true);
$response->saveAs('/tmp/confidential.pdf');
```

---

## Error Handling

The SDK throws typed exceptions for all API error responses:

```php
use Avraapi\Apix\Exceptions\ApixAuthenticationException;
use Avraapi\Apix\Exceptions\ApixInsufficientFundsException;
use Avraapi\Apix\Exceptions\ApixRateLimitException;
use Avraapi\Apix\Exceptions\ApixValidationException;
use Avraapi\Apix\Exceptions\ApixException;

try {
    $response = $apix->utilities()->generatePdf($html);
    $response->saveAs('/tmp/invoice.pdf');
} catch (ApixRateLimitException $e) {
    // HTTP 429 — rate limit exceeded
    echo "Rate limited. Retry after: " . $e->getMessage();
} catch (ApixInsufficientFundsException $e) {
    // HTTP 402 — wallet balance too low
    echo "Insufficient balance: " . $e->getMessage();
} catch (ApixValidationException $e) {
    // HTTP 422 — invalid input
    print_r($e->getValidationErrors());
} catch (ApixAuthenticationException $e) {
    // HTTP 401 — bad credentials
    echo "Auth failed: " . $e->getMessage();
} catch (ApixException $e) {
    // Catch-all for any other APIX error
    echo "[{$e->getErrorCode()}] {$e->getMessage()}";
}
```

---

## License

MIT — [Fidex Developers (Pvt) Ltd](https://avraapi.com)
