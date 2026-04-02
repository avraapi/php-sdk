<?php

declare(strict_types=1);

namespace Avraapi\Apix\Services;

use Avraapi\Apix\Responses\ApiResponse;

/**
 * SMS Service — QuickSend.lk backed messaging operations.
 *
 * Maps to the OpenAPI `SMS` tag.
 * Endpoint prefix: /sms
 *
 * All three send methods POST to the same endpoint (/sms/send) and are
 * differentiated by the `send_method` discriminator in the request body,
 * exactly as defined in the SmsSendRequest oneOf schema.
 *
 * @see https://avraapi.com/docs#tag/SMS
 */
final class SmsService extends AbstractService
{
    // ── Endpoints ─────────────────────────────────────────────────────────────

    /**
     * Send a single SMS message to one recipient.
     *
     * Wraps: POST /sms/send   { send_method: "single" }
     *
     * @param  string  $to       Sri Lankan mobile number (e.g. '0771234567').
     * @param  string  $message  Text content to deliver.
     *
     * @return ApiResponse  Success envelope. Key fields:
     *   $response->data['send_method']       — 'single'
     *   $response->data['message_count']     — int, should be 1
     *   $response->data['credits_charged']   — int
     *   $response->data['provider_response'] — array from QuickSend
     *
     * @throws \Avraapi\Apix\Exceptions\ApixValidationException
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException
     * @throws \Avraapi\Apix\Exceptions\ApixInsufficientFundsException
     * @throws \Avraapi\Apix\Exceptions\ApixRateLimitException
     * @throws \Avraapi\Apix\Exceptions\ApixException
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException
     *
     * Example:
     *   $response = $apix->sms()->sendSingle(
     *       to: '0771234567',
     *       message: 'Hello from APIX!',
     *   );
     *   echo $response->data['message_count']; // 1
     */
    public function sendSingle(string $to, string $message): ApiResponse
    {
        /** @var ApiResponse $response */
        $response = $this->post('/sms/send', [
            'send_method' => 'single',
            'to'          => $to,
            'message'     => $message,
        ]);

        return $response;
    }

    /**
     * Send the same SMS message to multiple recipients (broadcast).
     *
     * Wraps: POST /sms/send   { send_method: "bulk_same" }
     *
     * @param  list<string>  $recipients  Array of Sri Lankan mobile numbers (1–10,000).
     * @param  string        $message     Shared message body for all recipients.
     * @param  bool          $checkCost   When true, QuickSend returns pricing info
     *                                    without dispatching the messages.
     *
     * @return ApiResponse  Success envelope. Key fields:
     *   $response->data['send_method']       — 'bulk_same'
     *   $response->data['message_count']     — number of messages dispatched
     *   $response->data['credits_charged']   — int
     *   $response->data['provider_response'] — array from QuickSend
     *
     * @throws \Avraapi\Apix\Exceptions\ApixValidationException
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException
     * @throws \Avraapi\Apix\Exceptions\ApixInsufficientFundsException
     * @throws \Avraapi\Apix\Exceptions\ApixRateLimitException
     * @throws \Avraapi\Apix\Exceptions\ApixException
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException
     *
     * Example:
     *   $response = $apix->sms()->sendBulkSame(
     *       recipients: ['0771234567', '0777654321'],
     *       message: 'Broadcast from APIX!',
     *   );
     *   echo $response->data['message_count']; // 2
     */
    public function sendBulkSame(
        array $recipients,
        string $message,
        bool $checkCost = false,
    ): ApiResponse {
        /** @var ApiResponse $response */
        $response = $this->post('/sms/send', [
            'send_method' => 'bulk_same',
            'recipients'  => array_values($recipients),
            'message'     => $message,
            'check_cost'  => $checkCost,
        ]);

        return $response;
    }

    /**
     * Send a different message to each recipient.
     *
     * Wraps: POST /sms/send   { send_method: "bulk_different" }
     *
     * Each entry in $messages must be an associative array with:
     *   'to'  — Sri Lankan mobile number
     *   'msg' — Individual message body for that recipient
     *
     * Maximum 20 entries per request (gateway limit).
     *
     * @param  list<array{to: string, msg: string}>  $messages  Per-recipient message list.
     *
     * @return ApiResponse  Success envelope.
     *
     * @throws \Avraapi\Apix\Exceptions\ApixValidationException
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException
     * @throws \Avraapi\Apix\Exceptions\ApixInsufficientFundsException
     * @throws \Avraapi\Apix\Exceptions\ApixRateLimitException
     * @throws \Avraapi\Apix\Exceptions\ApixException
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException
     *
     * Example:
     *   $response = $apix->sms()->sendBulkDifferent([
     *       ['to' => '0771234567', 'msg' => 'Hello Alice!'],
     *       ['to' => '0777654321', 'msg' => 'Hello Bob!'],
     *   ]);
     */
    public function sendBulkDifferent(array $messages): ApiResponse
    {
        /** @var ApiResponse $response */
        $response = $this->post('/sms/send', [
            'send_method' => 'bulk_different',
            'msg_list'    => array_values($messages),
        ]);

        return $response;
    }

    /**
     * Check the QuickSend.lk SMS balance for this project's integration.
     *
     * Wraps: POST /sms/balance
     *
     * This request is always FREE — no wallet credits are deducted.
     *
     * @return ApiResponse  Success envelope. Key fields:
     *   $response->data['source']            — 'quicksend_direct' or 'apix_wallet'
     *   $response->data['balance_formatted'] — Human-readable balance string
     *   $response->data['provider_response'] — Raw QuickSend response (nullable)
     *
     * @throws \Avraapi\Apix\Exceptions\ApixAuthenticationException
     * @throws \Avraapi\Apix\Exceptions\ApixException
     * @throws \Avraapi\Apix\Exceptions\ApixNetworkException
     *
     * Example:
     *   $balance = $apix->sms()->getBalance();
     *   echo $balance->data['balance_formatted']; // '1500'
     */
    public function getBalance(): ApiResponse
    {
        /** @var ApiResponse $response */
        $response = $this->post('/sms/balance', []);

        return $response;
    }
}
