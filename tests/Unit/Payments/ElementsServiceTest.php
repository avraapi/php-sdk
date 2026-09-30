<?php

declare(strict_types=1);

namespace Avraapi\Apix\Tests\Unit\Payments;

use Avraapi\Apix\Payments\Elements\ElementsService;
use PHPUnit\Framework\TestCase;

final class ElementsServiceTest extends TestCase
{
    public function test_it_renders_only_an_avra_api_owned_versioned_elements_asset_and_fallback(): void
    {
        $html = (new ElementsService)->renderForm('customer-details', [
            'cdn_url' => 'https://gateway.avraapi.com/payment-elements',
            'version' => '1.0.0',
            'exclude' => ['district'],
        ]);

        self::assertStringContainsString('https://gateway.avraapi.com/payment-elements/v1.0.0/avraapi-payment-elements.umd.js', $html);
        self::assertStringContainsString('elements_script_missing', $html);
        self::assertStringNotContainsString('APIX_API_SECRET', $html);
    }

    public function test_it_allows_the_local_elements_bundle_only_in_development_and_uses_it_for_assets(): void
    {
        putenv('APIX_ENV=dev');
        putenv('PAYMENT_ELEMENTS_LOCAL_BUNDLE=true');
        try {
            $html = (new ElementsService)->renderForm('customer-details', [
                'cdn_url' => 'http://localhost:8002',
            ]);

            self::assertStringContainsString('http://localhost:8002/avraapi-payment-elements.umd.js', $html);
            self::assertStringContainsString('"assetBaseUrl":"http:\/\/localhost:8002"', $html);
            self::assertStringNotContainsString('/v1.0.0/', $html);
        } finally {
            putenv('APIX_ENV');
            putenv('PAYMENT_ELEMENTS_LOCAL_BUNDLE');
        }
    }

    public function test_it_uses_an_explicit_unversioned_development_bundle_for_an_https_cloudflare_host(): void
    {
        putenv('APIX_ENV=development');
        putenv('PAYMENT_ELEMENTS_LOCAL_BUNDLE=true');
        try {
            $html = (new ElementsService)->renderForm('customer-details', [
                'cdn_url' => 'https://unvs-gtw-tstng.avraapi.com',
            ]);

            self::assertStringContainsString('https://unvs-gtw-tstng.avraapi.com/avraapi-payment-elements.umd.js', $html);
            self::assertStringContainsString('"assetBaseUrl":"https:\/\/unvs-gtw-tstng.avraapi.com"', $html);
            self::assertStringNotContainsString('/v1.0.0/', $html);
        } finally {
            putenv('APIX_ENV');
            putenv('PAYMENT_ELEMENTS_LOCAL_BUNDLE');
        }
    }

    public function test_it_rejects_a_local_elements_bundle_outside_development(): void
    {
        putenv('APIX_ENV=prod');
        try {
            $this->expectException(\InvalidArgumentException::class);
            (new ElementsService)->renderForm('customer-details', [
                'cdn_url' => 'http://localhost:8002',
            ]);
        } finally {
            putenv('APIX_ENV');
        }
    }

    public function test_it_embeds_only_public_payment_availability_for_default_method_discovery(): void
    {
        $html = (new ElementsService)->renderMethods('payment-methods', [
            'availability' => [
                'ready' => true,
                'reason' => null,
                'message' => null,
                'methods' => [[
                    'gateway' => 'payhere',
                    'mode' => 'overlay',
                    'environment' => 'production',
                    'available' => true,
                ]],
            ],
        ]);

        self::assertStringContainsString('payment-methods--avraapi-payment-availability', $html);
        self::assertStringContainsString('"gateway":"payhere"', $html);
        self::assertStringNotContainsString('APIX_API_SECRET', $html);
        self::assertStringNotContainsString('credentials_ciphertext', $html);
    }
}
