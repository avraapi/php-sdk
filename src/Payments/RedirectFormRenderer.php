<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use LogicException;

final class RedirectFormRenderer
{
    public static function render(PaymentSession $session, string $submitLabel = 'Continue to payment'): string
    {
        if ($session->mode !== CheckoutMode::Redirect || ($session->checkout['type'] ?? null) !== 'redirect_form') {
            throw new LogicException('A redirect form can only be rendered for a redirect payment session.');
        }
        $action = self::escape((string) ($session->checkout['action_url'] ?? ''));
        $fields = $session->checkout['fields'] ?? [];
        if ($action === '' || ! is_array($fields)) {
            throw new LogicException('Payment session does not contain a redirect form.');
        }
        $inputs = '';
        foreach ($fields as $name => $value) {
            if (! is_scalar($value)) {
                continue;
            }
            $inputs .= '<input type="hidden" name="'.self::escape((string) $name).'" value="'.self::escape((string) $value).'">';
        }

        return '<form method="post" action="'.$action.'">'.$inputs.'<button type="submit">'.self::escape($submitLabel).'</button></form>';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
