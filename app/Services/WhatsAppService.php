<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected ?string $token = null;
    protected ?string $lastError = null;

    public function __construct()
    {
        $this->token = config('services.fonnte.token') ?? env('FONNTE_TOKEN') ?? '';
    }

    /**
     * Get the last error message from the API call.
     *
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Send a WhatsApp message using Fonnte API.
     *
     * @param string $target Recipient phone number (e.g. 08123456789 or 628123456789)
     * @param string $message Text message content
     * @return bool
     */
    public function sendMessage(string $target, string $message): bool
    {
        $this->lastError = null;

        if (empty($this->token)) {
            $this->lastError = "Token Fonnte belum dikonfigurasi di file .env";
            Log::warning("Fonnte Token is not set in env. WhatsApp message was not sent to: {$target}. Message: {$message}");
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62', // Default Indonesia
            ]);

            $responseData = $response->json();

            if ($response->successful() && ($responseData['status'] ?? false) === true) {
                Log::info("WhatsApp message successfully sent to {$target} via Fonnte.");
                return true;
            }

            $this->lastError = $responseData['reason'] ?? $responseData['message'] ?? $response->body();
            Log::error("Fonnte API returned error for {$target}: " . $this->lastError);
            return false;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Log::error("Failed to send WhatsApp message to {$target}: " . $this->lastError);
            return false;
        }
    }
}
