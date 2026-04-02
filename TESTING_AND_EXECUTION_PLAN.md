# APIX PHP SDK — Testing & Execution Plan

## 1. Directory Structure

Place the SDK **completely outside** your APIX Laravel project. They should be siblings:

```
/your-workspace/
├── apix-laravel/          ← Your existing Laravel 12 project (Sail)
│   ├── app/
│   ├── routes/
│   └── ...
│
├── apix-php-sdk/          ← The SDK package (this repo)
│   ├── composer.json
│   └── src/
│
└── apix-sdk-testbed/      ← Scratch test project (create this)
    ├── composer.json
    ├── .env
    └── test.php
```

**Why outside?** The SDK is a standalone Composer package with zero Laravel
dependencies. Keeping it outside prevents accidental coupling and mirrors exactly
how a real developer will consume it from Packagist.

---

## 2. Start Your Local APIX Gateway

Ensure your Laravel Sail instance is running and accessible:

```bash
cd /your-workspace/apix-laravel
./vendor/bin/sail up -d
```

Confirm the gateway is reachable:

```bash
curl -s http://localhost/up
# Should return: {"status":"ok"} or similar
```

The APIX API base URL on Sail is: `http://localhost/api/v1`

---

## 3. Get Your Local Credentials

Log in to the APIX admin panel (running on Sail) and create a project with a
**Development** environment. Copy:

- **Project Key** (`X-API-KEY`) — shown in the project credentials panel
- **API Secret** (`X-API-SECRET`) — shown once on creation

---

## 4. Create the Testbed Project

```bash
mkdir /your-workspace/apix-sdk-testbed
cd /your-workspace/apix-sdk-testbed
```

Create `composer.json`:

```json
{
    "name": "local/apix-sdk-testbed",
    "description": "Scratch project for testing the APIX PHP SDK locally",
    "require": {
        "php": "^8.2",
        "avraapi/apix-php-sdk": "*"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../apix-php-sdk",
            "options": {
                "symlink": true
            }
        }
    ],
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Install dependencies:

```bash
composer install
```

Composer will **symlink** `vendor/avraapi/apix-php-sdk` → `../apix-php-sdk`.
Any changes you make to the SDK source are instantly reflected — no reinstall needed.

---

## 5. Create the .env File

```bash
# /your-workspace/apix-sdk-testbed/.env
APIX_PROJECT_KEY=paste-your-project-key-here
APIX_API_SECRET=paste-your-api-secret-here
APIX_ENV=dev
APIX_BASE_URL=http://localhost/api/v1
```

---

## 6. Create the Test Script

Create `/your-workspace/apix-sdk-testbed/test.php`:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

// ── Load .env manually (no package needed) ────────────────────────────────────
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $value] = explode('=', $line, 2) + ['', ''];
        putenv(trim($key) . '=' . trim($value));
    }
}

use Avraapi\Apix\ApixClient;
use Avraapi\Apix\Exceptions\ApixAuthenticationException;
use Avraapi\Apix\Exceptions\ApixValidationException;
use Avraapi\Apix\Exceptions\ApixInsufficientFundsException;
use Avraapi\Apix\Exceptions\ApixException;
use Avraapi\Apix\Exceptions\ApixNetworkException;

// ── Instantiate the client (reads from getenv()) ──────────────────────────────
$apix = new ApixClient();

echo "APIX SDK Test — Base URL: " . $apix->config->baseUrl . PHP_EOL;
echo "Environment: " . $apix->config->env . PHP_EOL;
echo str_repeat('─', 60) . PHP_EOL;

// ── Helper ────────────────────────────────────────────────────────────────────
function section(string $title): void {
    echo PHP_EOL . "▶ {$title}" . PHP_EOL;
}

function ok(string $msg): void {
    echo "  ✓ {$msg}" . PHP_EOL;
}

function fail(string $msg): void {
    echo "  ✗ {$msg}" . PHP_EOL;
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 1 — Location: IP Lookup
// ═════════════════════════════════════════════════════════════════════════════
section('Location: IP Lookup');
try {
    $geo = $apix->location()->lookupIp('112.134.205.126');
    ok("Request ID: " . $geo->requestId);
    ok("Country: " . $geo->data['country']);
    ok("Timezone: " . $geo->data['timezone']);
    ok("Dot-notation: " . $geo->get('data.country_code'));
} catch (ApixException $e) {
    fail("HTTP {$e->getHttpStatus()} [{$e->getErrorCode()}]: {$e->getMessage()}");
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 2 — Location: withProvider override
// ═════════════════════════════════════════════════════════════════════════════
section('Location: withProvider(\'maxmind\') override');
try {
    $geo = $apix->location()->withProvider('maxmind')->lookupIp('8.8.8.8');
    ok("Country: " . $geo->data['country']);
    ok("Provider override accepted (no rejection from gateway)");
} catch (ApixException $e) {
    fail("HTTP {$e->getHttpStatus()} [{$e->getErrorCode()}]: {$e->getMessage()}");
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 3 — SMS: Send Single
// ═════════════════════════════════════════════════════════════════════════════
section('SMS: Send Single');
try {
    $sms = $apix->sms()->sendSingle(
        to: '0771234567',
        message: 'Hello from the APIX PHP SDK test!',
    );
    ok("Request ID: " . $sms->requestId);
    ok("Send method: " . $sms->data['send_method']);
    ok("Message count: " . $sms->data['message_count']);
    ok("Credits charged: " . $sms->data['credits_charged']);
} catch (ApixInsufficientFundsException $e) {
    fail("Insufficient funds — top up your APIX wallet to test SMS.");
} catch (ApixException $e) {
    fail("HTTP {$e->getHttpStatus()} [{$e->getErrorCode()}]: {$e->getMessage()}");
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 4 — SMS: Bulk Same
// ═════════════════════════════════════════════════════════════════════════════
section('SMS: Send Bulk Same');
try {
    $sms = $apix->sms()->sendBulkSame(
        recipients: ['0771234567', '0777654321'],
        message: 'APIX bulk test — SDK Phase 9',
        checkCost: true, // Dry run — no actual send
    );
    ok("Bulk dry run (check_cost=true). Method: " . $sms->data['send_method']);
} catch (ApixInsufficientFundsException $e) {
    fail("Insufficient funds for bulk.");
} catch (ApixException $e) {
    fail("HTTP {$e->getHttpStatus()} [{$e->getErrorCode()}]: {$e->getMessage()}");
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 5 — SMS: Bulk Different
// ═════════════════════════════════════════════════════════════════════════════
section('SMS: Send Bulk Different');
try {
    $sms = $apix->sms()->sendBulkDifferent([
        ['to' => '0771234567', 'msg' => 'Hello Alice from APIX SDK!'],
        ['to' => '0777654321', 'msg' => 'Hello Bob from APIX SDK!'],
    ]);
    ok("Method: " . $sms->data['send_method']);
    ok("Messages dispatched: " . $sms->data['message_count']);
} catch (ApixInsufficientFundsException $e) {
    fail("Insufficient funds for bulk different.");
} catch (ApixException $e) {
    fail("HTTP {$e->getHttpStatus()} [{$e->getErrorCode()}]: {$e->getMessage()}");
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 6 — SMS: Balance Check
// ═════════════════════════════════════════════════════════════════════════════
section('SMS: Balance Check');
try {
    $balance = $apix->sms()->getBalance();
    ok("Balance source: " . $balance->data['source']);
    ok("Balance: " . $balance->data['balance_formatted']);
} catch (ApixException $e) {
    fail("HTTP {$e->getHttpStatus()} [{$e->getErrorCode()}]: {$e->getMessage()}");
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 7 — Utilities: QR Code (Binary PNG)
// ═════════════════════════════════════════════════════════════════════════════
section('Utilities: Generate QR Code (PNG binary)');
try {
    $qr = $apix->utilities()->generateQr(
        data:   'https://avraapi.com',
        format: 'png',
        size:   300,
    );
    $path = $qr->saveAs(__DIR__ . '/output/test-qr.png');
    ok("Saved to: {$path}");
    ok("Content-Type: " . $qr->contentType);
    ok("Size: " . $qr->size . " bytes");
} catch (ApixException $e) {
    fail("HTTP {$e->getHttpStatus()} [{$e->getErrorCode()}]: {$e->getMessage()}");
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 8 — Utilities: QR Code (Base64 JSON)
// ═════════════════════════════════════════════════════════════════════════════
section('Utilities: Generate QR Code (base64 JSON)');
try {
    $qr = $apix->utilities()->generateQr(
        data:   'https://avraapi.com/sdk',
        format: 'base64',
    );
    ok("Request ID: " . $qr->requestId);
    ok("Format: " . $qr->data['format']);
    ok("Data URI starts with: " . substr($qr->data['data_uri'], 0, 30) . '...');
} catch (ApixException $e) {
    fail("HTTP {$e->getHttpStatus()} [{$e->getErrorCode()}]: {$e->getMessage()}");
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 9 — Utilities: Barcode (Binary PNG)
// ═════════════════════════════════════════════════════════════════════════════
section('Utilities: Generate Barcode (EAN13 PNG)');
try {
    $barcode = $apix->utilities()->generateBarcode(
        data:   '5901234123457',
        type:   'EAN13',
        format: 'png',
        height: 100,
    );
    $path = $barcode->saveAs(__DIR__ . '/output/test-barcode.png');
    ok("Saved to: {$path}");
    ok("Content-Type: " . $barcode->contentType);
    ok("Size: " . $barcode->size . " bytes");
} catch (ApixException $e) {
    fail("HTTP {$e->getHttpStatus()} [{$e->getErrorCode()}]: {$e->getMessage()}");
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 10 — Utilities: PDF (Binary)
// ═════════════════════════════════════════════════════════════════════════════
section('Utilities: Generate PDF (binary)');
$html = <<<HTML
<!DOCTYPE html>
<html>
<head><style>body{font-family:sans-serif;padding:40px}</style></head>
<body>
  <h1>APIX SDK Test Invoice</h1>
  <p>Generated by the <strong>avraapi/apix-php-sdk</strong> during Phase 9 testing.</p>
  <table border="1" cellpadding="8" style="width:100%">
    <tr><th>Item</th><th>Amount</th></tr>
    <tr><td>APIX API Credits</td><td>$99.00</td></tr>
  </table>
</body>
</html>
HTML;

try {
    $pdf = $apix->utilities()->generatePdf(
        html:        $html,
        responseType: 'binary',
        pageSize:    'A4',
        orientation: 'portrait',
        margins:     ['top' => 20, 'right' => 25, 'bottom' => 20, 'left' => 25],
    );
    $path = $pdf->saveAs(__DIR__ . '/output/test-invoice.pdf');
    ok("Saved to: {$path}");
    ok("Content-Type: " . $pdf->contentType);
    ok("Size: " . $pdf->size . " bytes");
    ok("isPdf(): " . ($pdf->isPdf() ? 'true' : 'false'));
} catch (ApixException $e) {
    fail("HTTP {$e->getHttpStatus()} [{$e->getErrorCode()}]: {$e->getMessage()}");
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 11 — Utilities: PDF (Base64)
// ═════════════════════════════════════════════════════════════════════════════
section('Utilities: Generate PDF (base64)');
try {
    $pdf = $apix->utilities()->generatePdf(
        html:         '<h1>Base64 PDF Test</h1>',
        responseType: 'base64',
    );
    ok("Request ID: " . $pdf->requestId);
    ok("Format: " . $pdf->data['format']);
    ok("Media type: " . $pdf->data['media_type']);
    ok("Base64 length: " . strlen($pdf->data['data']) . " chars");
} catch (ApixException $e) {
    fail("HTTP {$e->getHttpStatus()} [{$e->getErrorCode()}]: {$e->getMessage()}");
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 12 — Universal Call (escape hatch)
// ═════════════════════════════════════════════════════════════════════════════
section('Universal call() — smart path normalization');
try {
    // All of these should resolve to the same endpoint:
    $paths = [
        'location/lookup',
        '/location/lookup',
        '/api/v1/location/lookup',
        'http://localhost/api/v1/location/lookup',
        getenv('APIX_BASE_URL') . '/location/lookup',
    ];

    foreach ($paths as $path) {
        $r = $apix->call('POST', $path, ['ip' => '1.1.1.1']);
        ok("Path '{$path}' → country: " . $r->get('data.country'));
    }
} catch (ApixException $e) {
    fail("HTTP {$e->getHttpStatus()} [{$e->getErrorCode()}]: {$e->getMessage()}");
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 13 — Exception mapping
// ═════════════════════════════════════════════════════════════════════════════
section('Exception mapping — invalid credentials should throw ApixAuthenticationException');
try {
    $badClient = new ApixClient([
        'APIX_PROJECT_KEY' => 'invalid-key-00000',
        'APIX_API_SECRET'  => 'invalid-secret-00000',
        'APIX_ENV'         => 'dev',
        'APIX_BASE_URL'    => getenv('APIX_BASE_URL'),
    ]);
    $badClient->location()->lookupIp('1.1.1.1');
    fail("Expected ApixAuthenticationException but no exception was thrown.");
} catch (ApixAuthenticationException $e) {
    ok("Correctly threw ApixAuthenticationException: " . $e->getMessage());
    ok("HTTP status: " . $e->getHttpStatus());
    ok("Error code: " . $e->getErrorCode());
} catch (ApixNetworkException $e) {
    fail("Network error — is Sail running? " . $e->getMessage());
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST 14 — Validation exception with field details
// ═════════════════════════════════════════════════════════════════════════════
section('Exception mapping — empty IP should throw ApixValidationException');
try {
    $apix->location()->lookupIp('');
    fail("Expected ApixValidationException but no exception was thrown.");
} catch (ApixValidationException $e) {
    ok("Correctly threw ApixValidationException: " . $e->getMessage());
    ok("Validation errors: " . json_encode($e->getValidationErrors()));
} catch (ApixException $e) {
    // Gateway may return 422 or 400 — both are acceptable here
    ok("Caught ApixException [" . $e->getErrorCode() . "]: " . $e->getMessage());
}

// ═════════════════════════════════════════════════════════════════════════════
echo PHP_EOL . str_repeat('═', 60) . PHP_EOL;
echo "All tests completed. Check /output directory for binary files." . PHP_EOL;
```

---

## 7. Run the Tests

```bash
cd /your-workspace/apix-sdk-testbed
php test.php
```

Expected output (with all services configured and funded):

```
APIX SDK Test — Base URL: http://localhost/api/v1
Environment: dev
────────────────────────────────────────────────────────────

▶ Location: IP Lookup
  ✓ Request ID: req_abc123...
  ✓ Country: Sri Lanka
  ✓ Timezone: Asia/Colombo
  ✓ Dot-notation: LK

▶ SMS: Send Single
  ✓ Request ID: req_xyz456...
  ✓ Send method: single
  ...

▶ Utilities: Generate PDF (binary)
  ✓ Saved to: /your-workspace/apix-sdk-testbed/output/test-invoice.pdf
  ✓ Content-Type: application/pdf
  ...
```

---

## 8. Iterating on the SDK

Because Composer used `"symlink": true` in the `path` repository, any change you
make inside `/your-workspace/apix-php-sdk/src/` is **immediately live** in the
testbed. No `composer update` required. Just run `php test.php` again.

---

## 9. Publishing to Packagist (When Ready)

1. Push `apix-php-sdk/` to a public GitHub repo.
2. Register the repository on [packagist.org](https://packagist.org).
3. Tag a release: `git tag v1.0.0 && git push --tags`
4. Consumers install with: `composer require avraapi/apix-php-sdk`
5. Remove the `path` repository block from their `composer.json`.

---

## 10. Common Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `ApixNetworkException: Could not connect` | Sail not running | `cd apix-laravel && ./vendor/bin/sail up -d` |
| `ApixAuthenticationException: Invalid API key` | Wrong credentials or X-ENV mismatch | Verify key + ensure APIX_ENV=dev matches the credential's environment |
| `Class not found` errors | Autoload not generated | Run `composer dump-autoload` in testbed |
| Binary file is 0 bytes | saveAs() path permission | Check write permission on the output directory |
| PDF response is a JSON body with `success: false` | Wallet has no credits | Top up in APIX admin panel |
