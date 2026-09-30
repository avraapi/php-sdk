# AvraAPI PHP SDK

Official PHP SDK for the [AvraAPI (APIX)](https://avraapi.com) enterprise API gateway.

Zero Laravel dependencies — works in any PHP 8.2+ project.

> **Full documentation & guides:** [https://avraapi.com/developers/sdks](https://avraapi.com/developers/sdks)

```bash
composer require avraapi/php-sdk
```

## PayHere advanced payments (server-side only)

Preapproval, authorization, charging, capture, and sensitive callback verification are deliberately unavailable to browser code. Enable the project-level advanced PayHere gate, the matching Vault permissions, and both risk acknowledgements first. APIX returns a preapproval `customer_token` or authorization `authorization_token` only through `verifySensitiveCallback()` with `Cache-Control: no-store`; store it in your own encrypted server-side vault or delete it. Do not serialize, queue, log, or send that object to a browser.

```php
$session = $apix->payment()->payhere()->preapprovals()->create($options);
// Redirect the customer with RedirectFormRenderer; then receive PayHere notify_url server-side.
$result = $apix->payment()->verifySensitiveCallback($callbackOptions);
$customerToken = $result->artifact->value; // merchant-server scope only
```

For a later explicit charge or capture, use `payhere()->charges()->create(...)` or `payhere()->captures()->create(...)` with a unique idempotency key and the confirmation argument set to `true`. APIX never retries these commands automatically.

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
| Security | `$apix->security()` | VPN & Proxy Shield, Burner Email Detection |
| Currency | `$apix->currency()` | Currency codes, live rates, pair rates, conversion |
| Payments | `$apix->payment()` | Universal checkout preparation and merchant-server completion |

## Payment availability and default Elements discovery

Payment availability is evaluated on your server using the authenticated AvraAPI
project credentials. It confirms the current Workspace UPG slot, plan gateway
entitlement, and active Vault configuration before returning browser-safe method
metadata:

```php
$availability = $apix->payment()->availability('shop.example.com');

if (! $availability->isReady()) {
    // Use $availability->reason and $availability->message in your merchant UI.
    // Do not send APIX credentials or raw SDK exceptions to the browser.
}
```

The simplest Elements integration is server-rendered discovery:

```php
echo $apix->payment()->renderMethods(
    mountId: 'payment-methods',
    merchantDomain: 'shop.example.com',
);
```

```js
const elements = AvraAPIPaymentElements.create({ createOrder });
elements.renderMethods('#payment-methods');
```

When no provider environment is explicitly selected, AvraAPI chooses an active
production Vault profile before sandbox. Explicit SDK gateway-environment
overrides and an explicit browser `methods` array take precedence. A released
Workspace slot blocks new checkout even when historic gateway configurations
remain; the availability bootstrap displays a safe unavailable state instead.
Existing signed payment sessions may still complete after release.

## PayHere Checkout (server-side)

Configure PayHere first in the AvraAPI Dashboard's Universal Payment Gateway
Control Centre. Keep this SDK, and therefore your AvraAPI project secret, on
your application server only.

```php
use Avraapi\Apix\ApixClient;
use Avraapi\Apix\Payments\CheckoutMode;
use Avraapi\Apix\Payments\CreateOrderOptions;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\RedirectFormRenderer;

$session = $apix->payment()->createOrder(new CreateOrderOptions(
    gateway: GatewayCode::PayHere,
    mode: CheckoutMode::Redirect,
    orderId: 'order_10001',
    items: 'Premium membership',
    amount: '1500.00', // pass decimal strings, never floats
    currency: 'LKR',
    customer: [
        'first_name' => 'Saman', 'last_name' => 'Perera',
        'email' => 'saman@example.com', 'phone' => '0771234567',
        'address' => 'No. 1, Galle Road', 'city' => 'Colombo', 'country' => 'Sri Lanka',
    ],
    urls: [
        'return_url' => 'https://shop.example.com/payments/return',
        'cancel_url' => 'https://shop.example.com/payments/cancel',
        'notify_url' => 'https://shop.example.com/payments/payhere/notify',
    ],
));

// Echo only from a merchant-server endpoint/view. The renderer escapes every field.
echo RedirectFormRenderer::render($session, 'Pay with PayHere');
```

For a PayHere overlay, create the session with `CheckoutMode::Overlay` and pass
only the returned public `checkout` data to the browser package. Never send the
`ApixClient`, API key, or API secret to the browser.

Your merchant-owned `notify_url` must complete the original form payload before
marking an order paid. Browser return and overlay completion are not proof of a
successful payment. Store `$session->completionContext` with your own pending
order; it is signed, short-lived, server-only data and must never be sent to a
browser.

```php
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\PaymentCompletionOptions;
use Avraapi\Apix\Payments\PaymentResponseOptions;

$result = $apix->payment()->completePayment(new PaymentCompletionOptions(
    gateway: GatewayCode::PayHere,
    completionContext: $merchantOrder->payment_completion_context,
    payload: $_POST, // explicit parsed arrays are preferred in frameworks
));

if ($result->verified && $result->paymentStatus->value === 'succeeded') {
    // Persist the outcome in YOUR merchant database.
}
```

`PaymentResponseOptions::short()` is the default and returns the same compact
result for every gateway. Use `include(['callback.card_no'])` for exact
provider-native fields, or `full()` for the complete upstream response body
under `$result->nativeOperations`. `full()` and `include()` are server-only and
must be handled according to your gateway agreement; APIX never logs, caches,
queues, or stores those bodies.

`$result->reconciliation` is safe gateway-neutral metadata describing the
second provider observation: `matched`, `conflict`, `unavailable`, or
`not_applicable`. With the default `reconcileProvider: true`, a successful
PayHere callback uses Merchant API Retrieval when that optional profile is
enabled; a successful PayPlus callback uses the provider's signed JSON status
request; and KOKO uses signed Order View. Do not fulfil an order when its
reconciled status is `unknown`, `pending`, or its reconciliation state is
`conflict`/`unavailable`. A conflict deliberately retains the provider's
authoritative state and exposes both status values in this metadata.

MarxPay uses the exact same method after its browser return:

```php
$result = $apix->payment()->completePayment(new PaymentCompletionOptions(
    GatewayCode::MarxPay,
    $merchantOrder->payment_completion_context,
    ['merchantRID' => $_GET['merchantRID'], 'trId' => $_GET['trId']],
    PaymentResponseOptions::full(), // yields native_operations.initiate + .summary
));
```

An SDK `return_url` or `notify_url` overrides the optional Vault default only
for that request. It must use a configured merchant origin and is never written
back to the AvraAPI Vault. Omit an override to use the saved default.

## DirectPay V3 one-time checkout

Configure the DirectPay Merchant ID, API Key, Merchant Secret Key, and a
public **Response URL** in the AvraAPI Control Centre. APIX signs the public
checkout payload with the vaulted Merchant Secret Key; neither that secret nor
the API Key is sent to Payment Elements.

```php
use Avraapi\Apix\Payments\CheckoutMode;
use Avraapi\Apix\Payments\CreateOrderOptions;
use Avraapi\Apix\Payments\GatewayCode;

$session = $apix->payment()->createOrder(new CreateOrderOptions(
    gateway: GatewayCode::DirectPay,
    mode: CheckoutMode::Overlay, // or CheckoutMode::Embedded
    orderId: 'order_10002',
    items: 'Premium membership',
    amount: '1500.00',
    currency: 'LKR',
    customer: $customer,
    urls: [
        // DirectPay's server-to-server signed callback endpoint.
        'notify_url' => 'https://shop.example.com/payments/directpay/response',
        'return_url' => 'https://shop.example.com/payments/directpay/return',
    ],
    // DirectPay's own browser script logs its signed input. Customer prefill
    // is optional and intentionally disabled unless a merchant opts in.
    providerOptions: ['prefill_customer' => false],
));
// Store $session->completionContext with the merchant's own pending order.
// Pass only $session->checkout to Payment Elements in the browser.
```

For an embedded checkout, either pass `directPayContainer: '#directpay-card'`
when creating Payment Elements or let it create a secure mount under the
rendered payment-method component. DirectPay's browser promise is UI feedback,
not payment proof. Complete the signed server callback instead:

```php
use Avraapi\Apix\Payments\DirectPay\DirectPayCallbackPayload;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\PaymentCompletionOptions;
use Avraapi\Apix\Payments\PaymentResponseOptions;

$rawBody = file_get_contents('php://input');
$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

$result = $apix->payment()->completePayment(new PaymentCompletionOptions(
    gateway: GatewayCode::DirectPay,
    completionContext: $merchantOrder->payment_completion_context,
    payload: (new DirectPayCallbackPayload($rawBody, $authorization))->toArray(),
    response: PaymentResponseOptions::full(),
));
```

DirectPay redirect checkout, stored-card management, tokenized 3DS payments,
recurring schedules, authorization, capture, void, and refund operations are
intentionally unavailable until DirectPay provides their complete production
contract and test fixtures.

## PayPlus standard hosted checkout

PayPlus currently provides a server-created, hosted checkout—not a browser
overlay or iframe SDK. Configure the Merchant ID, Application Key, Secret Key,
one exact merchant origin, and default notification/return URLs in the AvraAPI
Control Centre. APIX Base64-encodes and HMAC-signs the provider request using
the vaulted Secret Key, then validates the returned PayPlus HTTPS URL.

```php
use Avraapi\Apix\Payments\CheckoutMode;
use Avraapi\Apix\Payments\CreateOrderOptions;
use Avraapi\Apix\Payments\GatewayCode;

$session = $apix->payment()->createOrder(new CreateOrderOptions(
    gateway: GatewayCode::PayPlus,
    mode: CheckoutMode::Redirect,
    orderId: 'order_10003',
    items: 'Premium membership',
    amount: '1500.00',
    currency: 'LKR',
    customer: [
        'first_name' => 'Saman', 'last_name' => 'Perera',
        'email' => 'saman@example.com', 'phone' => '+94771234567',
        'phone_dial_code' => '+94',
        'address' => 'No. 1, Galle Road', 'city' => 'Colombo', 'country' => 'Sri Lanka',
    ],
    urls: [
        'notify_url' => 'https://shop.example.com/payments/payplus/notify',
        'return_url' => 'https://shop.example.com/payments/payplus/return',
    ],
));

// Store $session->completionContext on the pending merchant order, then redirect:
header('Location: '.$session->checkout['redirect_url'], true, 303);
exit;
```

Only the signed server callback is payment proof. Preserve its raw Base64 body
and Authorization header exactly; APIX verifies them before decoding the JSON:

```php
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\PayPlus\PayPlusCallbackPayload;
use Avraapi\Apix\Payments\PaymentCompletionOptions;
use Avraapi\Apix\Payments\PaymentResponseOptions;

$result = $apix->payment()->completePayment(new PaymentCompletionOptions(
    gateway: GatewayCode::PayPlus,
    completionContext: $merchantOrder->payment_completion_context,
    payload: (new PayPlusCallbackPayload(
        file_get_contents('php://input'),
        $_SERVER['HTTP_AUTHORIZATION'] ?? '',
    ))->toArray(),
    response: PaymentResponseOptions::full(),
));
```

Use `$apix->payment()->payplus()->status('order_10003')` only for provider
reconciliation. It does not replace verified callback completion. LankaQR,
JustPay recurring/token operations, stored cards, and any unadvertised wallet
or crypto methods remain unavailable in this release.

## KOKO Buy Now, Pay Later checkout

Configure the KOKO Merchant ID, API Key, merchant private PEM, KOKO public
PEM, default notification/return URLs, and Cancel URL in the AvraAPI Control
Centre. KOKO's provider-required checkout is a top-level POST form, so use the
same `createOrder()` contract and hand the returned checkout object to Payment
Elements (or render it server-side with `RedirectFormRenderer`). The merchant
private key never leaves AvraAPI.

```php
use Avraapi\Apix\Payments\CheckoutMode;
use Avraapi\Apix\Payments\CreateOrderOptions;
use Avraapi\Apix\Payments\GatewayCode;

$session = $apix->payment()->createOrder(new CreateOrderOptions(
    gateway: GatewayCode::Koko,
    mode: CheckoutMode::Redirect,
    orderId: 'order_10004',
    items: 'Premium membership',
    amount: '1500.00',
    currency: 'LKR',
    customer: $customer,
    urls: [
        'notify_url' => 'https://shop.example.com/payments/koko/notify',
        'return_url' => 'https://shop.example.com/payments/koko/return',
        'cancel_url' => 'https://shop.example.com/payments/koko/cancel',
    ],
));
```

KOKO's signed server notification is the normal completion path:

```php
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\Koko\KokoCallbackPayload;
use Avraapi\Apix\Payments\PaymentCompletionOptions;

$result = $apix->payment()->completePayment(new PaymentCompletionOptions(
    GatewayCode::Koko,
    $merchantOrder->payment_completion_context,
    KokoCallbackPayload::fromForm($_POST)->toArray(),
));
```

`completePayment()` reconciles a KOKO completion with KOKO's independently
signed Order View response by default. This gives the newest authenticated
status precedence over a callback that may have been emitted while the order
was still changing. A merchant may skip only this additional KOKO lookup for a
verified server callback:

```php
new PaymentCompletionOptions(
    GatewayCode::Koko,
    $merchantOrder->payment_completion_context,
    KokoCallbackPayload::fromForm($_POST)->toArray(),
    reconcileProvider: false,
)
```

An unsigned browser return always performs Order View reconciliation; it cannot
be trusted by opting out.

For a browser return or cancellation, use `KokoReturnPayload::fromQuery($_GET)`
with the same `completePayment()` call. APIX reconciles the unsigned browser
values through KOKO's independently signed Order View response before it
returns a completion result. `orderView('order_10004')` remains available for
merchant diagnostics and reconciliation.

## OnePay standard redirect checkout

Configure the OnePay App ID, App Token, Hash Salt, Dashboard Notification URL,
and default HTTPS Return URL in the AvraAPI Control Centre. `createOrder()`
creates the OnePay checkout link on the merchant server; the App Token and
Hash Salt never reach the browser. OnePay supports the shared `redirect` mode
and the official SDK-backed `overlay` mode. Overlay creation remains the same
server call; APIX returns a public provider URL and transaction ID to Elements,
which calls OnePay's `processDirectPayment()` without receiving any credential.

```php
use Avraapi\Apix\Payments\CheckoutMode;
use Avraapi\Apix\Payments\CreateOrderOptions;
use Avraapi\Apix\Payments\GatewayCode;

$session = $apix->payment()->createOrder(new CreateOrderOptions(
    gateway: GatewayCode::OnePay,
    mode: CheckoutMode::Overlay, // Or CheckoutMode::Redirect for top-level navigation.
    orderId: 'order_10005',
    items: 'Premium membership',
    amount: '1500.00',
    currency: 'LKR',
    customer: $customer,
    urls: [
        'return_url' => 'https://shop.example.com/payments/onepay/return',
    ],
));
```

OnePay documents no callback signature. Its JSON Dashboard callback and browser
return are therefore completion triggers only. Pass either to the same
`completePayment()` operation; APIX always binds the expected OnePay transaction
ID and confirms `/v3/transaction/status/` before returning `succeeded`.

```php
use Avraapi\Apix\Payments\OnePay\OnePayCallbackPayload;
use Avraapi\Apix\Payments\PaymentCompletionOptions;

$callback = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
$result = $apix->payment()->completePayment(new PaymentCompletionOptions(
    GatewayCode::OnePay,
    $merchantOrder->payment_completion_context,
    (new OnePayCallbackPayload($callback))->toArray(),
));
```

For a browser return, use `new OnePayReturnPayload($_GET)`. The optional
`reconcileProvider: false` argument is accepted for cross-gateway API
compatibility but intentionally has no effect for OnePay: status reconciliation
remains mandatory. Merchant diagnostics may query
`$apix->payment()->onepay()->status($onePayTransactionId)` separately; it is
not a replacement for the bound completion call.

## PayHere Merchant APIs: retrieval and refunds

These capabilities require the separately configured App ID/App Secret profile,
explicit capability selection, merchant acknowledgement of AvraAPI's egress
allowlist, and an enabled AvraAPI feature flag. Retrieval returns a redacted
payment projection; customer and card details are deliberately excluded.

```php
$payments = $apix->payment()->payhere()->retrieval()->findByOrderId('ORDER-10001');

$refund = $apix->payment()->payhere()->refunds()->create(
    idempotencyKey: 'b1e6c230-6e8e-4c6a-bf59-merchant-generated-key',
    paymentId: '320027150501',
    authorizationToken: null,
    description: 'Item is out of stock',
    confirmRefund: true,
);
```

Refunds are irreversible commands. APIX records only an opaque HMAC of the
idempotency key for 24 hours and never stores the order, provider response, or
OAuth token. A repeated key returns an unknown-outcome error instead of risking
a second refund.

## PayHere Recurring Checkout and Subscription Manager

Enable the per-environment **Recurring PayHere checkout** flag in the Control
Centre and the AvraAPI release flag before using this capability. APIX prepares
and verifies a request only: your application must store customer consent,
subscription ID, schedule, callback history, and entitlement itself.

```php
use Avraapi\Apix\Payments\RecurringOrderOptions;

$session = $apix->payment()->payhere()->recurring()->create(
    new RecurringOrderOptions(
        gateway: GatewayCode::PayHere,
        mode: CheckoutMode::Redirect,
        orderId: 'membership_10001',
        items: 'Monthly membership',
        amount: '1500.00',
        currency: 'LKR',
        customer: $customer,
        urls: $urls,
        recurrence: '1 Month',
        duration: 'Forever',
        startupFee: null,
        recurringStartDate: '2026-10-01', // live environment only
        autoCancel: true,                 // live environment only
        maxRetries: 3,                    // live environment only
        isRecoveryDue: true,              // live environment only
    ),
);

echo RedirectFormRenderer::render($session, 'Start subscription');
```

For a recurring notification, set `flow: 'recurring'` when calling
`VerifyCallbackOptions`. The verified result contains only the normalized
subscription ID, message type, state, and next installment date; it excludes
card and customer data.

```php
$subscriptions = $apix->payment()->payhere()->subscriptions();
$active = $subscriptions->find('420075032251');

$retry = $subscriptions->retry(
    idempotencyKey: 'merchant-generated-unique-retry-key',
    subscriptionId: '420075032251',
    confirmRetry: true,
);

$cancel = $subscriptions->cancel(
    idempotencyKey: 'merchant-generated-unique-cancel-key',
    subscriptionId: '420075032251',
    confirmCancel: true,
);
```

Retry and cancellation are explicit, idempotent commands. APIX never retries
them automatically and cannot tell you a provider outcome after a timeout or
duplicate idempotency key.

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

## VPN & Proxy Shield

Detect VPNs, proxies, Tor exit nodes, iCloud Private Relay, and hosting/datacenter IPs.

```php
$result = $apix->security()->checkVpn('8.8.8.8');

echo $result->data['ip_address'];    // '8.8.8.8'
echo $result->data['is_vpn'];        // false
echo $result->data['is_proxy'];      // false
echo $result->data['is_tor'];        // false
echo $result->data['is_relay'];      // false
echo $result->data['is_hosting'];    // false
echo $result->data['country_code'];  // 'US'
echo $result->data['city'];          // 'Mountain View'
echo $result->data['asn'];           // '15169'
echo $result->data['network_name'];  // 'Google LLC'
echo $result->data['provider_name']; // 'vpnapi' or 'iplocate'

// Quick threat check:
$d = $result->data;
$isThreat = $d['is_vpn'] || $d['is_proxy'] || $d['is_tor'];
```

---

## Burner Email Shield

Detect temporary and disposable email addresses using a dual-list Redis lookup (7,000+ domains).

```php
$result = $apix->security()->checkBurnerEmail('user@mailinator.com');

echo $result->data['email'];             // 'user@mailinator.com'
echo $result->data['domain'];            // 'mailinator.com'
echo $result->data['is_valid_syntax'];   // true
echo $result->data['is_disposable'];     // true
echo $result->data['source'];            // 'global', 'custom', or 'none'
echo $result->data['execution_time_ms']; // 0.42

// Guard a registration form:
if ($result->data['is_disposable']) {
    throw new \Exception('Disposable emails are not allowed.');
}
```

---

## Multi-Currency Rates & Conversion

Free currency exchange rate API — 160+ currencies, 2-hour cached rates, zero credit cost.

### Get All Currency Codes

```php
$result = $apix->currency()->getCodes();

echo $result->data['count']; // 161
foreach ($result->data['codes'] as $c) {
    echo "{$c['code']} — {$c['name']}\n"; // 'USD — United States Dollar'
}
```

### Get Latest Rates from a Base Currency

```php
$result = $apix->currency()->getLatestRates('USD');

echo $result->data['base'];              // 'USD'
echo $result->data['last_updated'];      // '2025-05-10T...'
echo $result->data['rates']['EUR'];      // 0.89123456
echo $result->data['rates']['LKR'];      // 298.50000000
```

### Get Pair Rate

```php
$result = $apix->currency()->getPairRate('USD', 'EUR');

echo $result->data['base'];         // 'USD'
echo $result->data['target'];       // 'EUR'
echo $result->data['rate'];         // 0.89123456
echo $result->data['last_updated']; // '2025-05-10T...'
```

### Convert an Amount

```php
$result = $apix->currency()->convert('USD', 'LKR', 100.00);

$d = $result->data;
echo "{$d['amount']} {$d['base']} = {$d['conversion_result']} {$d['target']}";
// "100 USD = 29850.000000 LKR"

echo $d['rate'];              // 298.50000000
echo $d['conversion_result']; // 29850.000000
echo $d['last_updated'];      // '2025-05-10T...'
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

## Documentation

For full API reference, usage guides, and interactive examples, visit:

**[https://avraapi.com/developers/sdks](https://avraapi.com/developers/sdks)**

---

## License

MIT — [Fidex Developers (Pvt) Ltd](https://avraapi.com)
