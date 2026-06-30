<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PastechSmsService
{
    private string $apiKey;
    private string $senderId;
    private string $endpoint;
    private string $balanceEndpoint;

    public const RESPONSE_MESSAGES = [
        '1000' => 'Message submitted successfully',
        '1002' => 'SMS sending failed',
        '1003' => 'Insufficient balance',
        '1004' => 'Invalid API key',
        '1005' => 'Invalid phone number',
        '1006' => 'Invalid sender ID. Sender ID must not be more than 11 characters (including white space).',
        '1007' => 'Message scheduled for later delivery',
        '1008' => 'Empty message',
    ];

    public function __construct()
    {
        $this->apiKey          = (string) config('services.pastech_sms.api_key');
        $this->senderId        = (string) config('services.pastech_sms.sender_id');
        $this->endpoint        = (string) config('services.pastech_sms.endpoint');
        $this->balanceEndpoint = (string) config('services.pastech_sms.balance_endpoint');
    }

    /**
     * Normalise a Ghanaian phone number to local 0-prefixed format
     * expected by the Pastech gateway.
     */
    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '233')) {
            $phone = '0' . substr($phone, 3);
        }

        return $phone;
    }

    /**
     * Send a single SMS message.
     *
     * @return array{success: bool, code: string, message: string}
     */
    public function sendSms(string $phone, string $message): array
    {
        $phone = $this->normalizePhone($phone);

        try {
            $response = Http::get($this->endpoint, [
                'key'       => $this->apiKey,
                'to'        => $phone,
                'msg'       => $message,
                'sender_id' => $this->senderId,
            ]);

            $body = trim($response->body());
            $code = $this->extractCode($body);

            $success = $code === '1000' || $code === '1007';

            if (! $success) {
                Log::error('Pastech SMS failed', ['phone' => $phone, 'code' => $code, 'body' => $body]);
            }

            return [
                'success' => $success,
                'code'    => $code,
                'message' => self::RESPONSE_MESSAGES[$code] ?? ('Unknown response: ' . $body),
            ];
        } catch (\Exception $e) {
            Log::error('Pastech SMS exception', ['phone' => $phone, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'code'    => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check remaining SMS balance.
     */
    public function checkBalance(): array
    {
        try {
            $response = Http::get($this->balanceEndpoint, ['key' => $this->apiKey]);

            return [
                'success' => $response->successful(),
                'body'    => trim($response->body()),
            ];
        } catch (\Exception $e) {
            Log::error('Pastech SMS balance check exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'body'    => $e->getMessage(),
            ];
        }
    }

    /**
     * The gateway returns a bare numeric code, but sometimes wraps it
     * in surrounding text — pull the 4-digit code out defensively.
     */
    private function extractCode(string $body): string
    {
        if (preg_match('/\b(1000|1002|1003|1004|1005|1006|1007|1008)\b/', $body, $m)) {
            return $m[1];
        }

        return $body;
    }
}
