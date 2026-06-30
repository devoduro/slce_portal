<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MNotifyService
{
    private string $apiKey;
    private string $senderId;
    private string $baseUrl = 'https://apps.mnotify.net/smsapi';

    public function __construct()
    {
        $this->apiKey   = config('services.mnotify.api_key');
        $this->senderId = config('services.mnotify.sender_id');
    }

    public function sendSms(string $phone, string $message): bool
    {
        // Normalise Ghanaian numbers: strip leading 0, prepend 233
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '233' . substr($phone, 1);
        }

        try {
            $response = Http::get($this->baseUrl, [
                'key'    => $this->apiKey,
                'to'     => $phone,
                'msg'    => $message,
                'sender' => $this->senderId,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('mNotify SMS failed', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error('mNotify SMS exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
