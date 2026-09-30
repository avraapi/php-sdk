<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\Elements;

use InvalidArgumentException;

/**
 * Server-rendered, secret-free mount helpers for @avraapi/payment-elements.
 * Browser execution is delegated to the independently versioned Elements bundle.
 */
final class ElementsService
{
    private const DEFAULT_CDN_URL = 'https://cdn.avraapi.com/payment-elements';

    /** @param array<string, mixed> $options */
    public function renderForm(string $mountId, array $options = []): string
    {
        return $this->render('form', $mountId, $options);
    }

    /** @param array<string, mixed> $options */
    public function renderMethods(string $mountId, array $options = []): string
    {
        return $this->render('methods', $mountId, $options);
    }

    /** @param array<string, mixed> $options */
    private function render(string $component, string $mountId, array $options): string
    {
        if (! preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,127}$/', $mountId)) {
            throw new InvalidArgumentException('Payment Elements mount ID must start with a letter and contain only letters, numbers, dashes, or underscores.');
        }
        $cdnUrl = $this->cdnUrl((string) ($options['cdn_url'] ?? getenv('PAYMENT_ELEMENTS_CDN_URL') ?: self::DEFAULT_CDN_URL));
        $version = trim((string) ($options['version'] ?? getenv('PAYMENT_ELEMENTS_VERSION') ?: '1.1.0'));
        if (! preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            throw new InvalidArgumentException('Payment Elements version must be an immutable semantic version.');
        }
        unset($options['cdn_url'], $options['version']);
        $nonce = isset($options['nonce']) ? ' nonce="'.htmlspecialchars((string) $options['nonce'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"' : '';
        unset($options['nonce']);
        $json = json_encode($options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
        $availabilityJson = $component === 'methods' && isset($options['availability']) && is_array($options['availability'])
            ? json_encode($options['availability'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR)
            : null;
        $createOptions = json_encode(['assetBaseUrl' => $cdnUrl], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
        $id = htmlspecialchars($mountId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $src = htmlspecialchars($this->scriptUrl($cdnUrl, $version, $this->usesUnversionedDevelopmentBundle()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $availabilityBootstrap = $availabilityJson === null
            ? ''
            : "<script{$nonce} type=\"application/json\" id=\"{$id}--avraapi-payment-availability\" data-avraapi-payment-availability-for=\"{$id}\">{$availabilityJson}</script>\n";

        return <<<HTML
<div id="{$id}" data-avraapi-elements-mount="{$component}" aria-live="polite"><p>AvraAPI Payment Elements is loading. If this message remains, verify the pinned CDN script and Content Security Policy (elements_script_missing).</p></div>
{$availabilityBootstrap}<script{$nonce} src="{$src}" defer onerror="document.getElementById('{$id}').textContent='AvraAPI Payment Elements could not load (elements_script_missing). Check the versioned CDN script and Content Security Policy.';"></script>
<script{$nonce}>window.addEventListener('DOMContentLoaded',function(){var target=document.getElementById('{$id}');if(!window.AvraAPIPaymentElements||!window.AvraAPIPaymentElements.create){target.textContent='AvraAPI Payment Elements is unavailable (elements_script_missing). Include {$src} before mounting this component.';return;}try{window.AvraAPIPaymentElements.create({$createOptions}).render{$this->studly($component)}(target,{$json});}catch(error){target.textContent='AvraAPI Payment Elements could not render ('+(error.code||'elements_render_failed')+').';}});</script>
HTML;
    }

    private function cdnUrl(string $value): string
    {
        $parts = parse_url($value);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $isAvraApiHost = $host === 'cdn.avraapi.com' || $host === 'gateway.avraapi.com' || str_ends_with($host, '.avraapi.com');
        if (($parts['scheme'] ?? '') === 'https' && $isAvraApiHost) {
            return rtrim($value, '/');
        }
        if ($this->isLocalDevelopmentUrl($parts)) {
            return rtrim($value, '/');
        }

        throw new InvalidArgumentException('Payment Elements CDN URL must be an AvraAPI-owned HTTPS origin. Localhost HTTP is permitted only when APIX_ENV is dev or development.');
    }

    private function scriptUrl(string $cdnUrl, string $version, bool $useUnversionedBundle): string
    {
        if ($useUnversionedBundle) {
            return "{$cdnUrl}/avraapi-payment-elements.umd.js";
        }

        return "{$cdnUrl}/v{$version}/avraapi-payment-elements.umd.js";
    }

    /** @param array<string, mixed>|false $parts */
    private function isLocalDevelopmentUrl(array|false $parts): bool
    {
        $environment = strtolower(trim((string) getenv('APIX_ENV')));
        $host = strtolower((string) ($parts['host'] ?? ''));

        return in_array($environment, ['dev', 'development'], true)
            && ($parts['scheme'] ?? '') === 'http'
            && in_array($host, ['localhost', '127.0.0.1'], true);
    }

    private function usesUnversionedDevelopmentBundle(): bool
    {
        $environment = strtolower(trim((string) getenv('APIX_ENV')));

        return in_array($environment, ['dev', 'development'], true)
            && filter_var(getenv('PAYMENT_ELEMENTS_LOCAL_BUNDLE') ?: false, FILTER_VALIDATE_BOOL);
    }

    private function studly(string $value): string
    {
        return ucfirst($value);
    }
}
