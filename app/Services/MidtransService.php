<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    protected string $merchantId;
    protected string $clientKey;
    protected string $serverKey;
    protected bool $isProduction;

    public function __construct()
    {
        $this->merchantId = config('services.midtrans.merchant_id') ?? env('MIDTRANS_MERCHANT_ID', '');
        $this->clientKey = config('services.midtrans.client_key') ?? env('MIDTRANS_CLIENT_KEY', '');
        $this->serverKey = config('services.midtrans.server_key') ?? env('MIDTRANS_SERVER_KEY', '');
        $this->isProduction = (bool) (config('services.midtrans.is_production') ?? env('MIDTRANS_IS_PRODUCTION', false));
    }

    /**
     * Get Midtrans Snap API Base URL.
     */
    protected function getSnapBaseUrl(): string
    {
        return $this->isProduction
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    /**
     * Get Server Key.
     */
    public function getServerKey(): string
    {
        return $this->serverKey;
    }

    /**
     * Create Snap Token from Payment.
     *
     * @param Payment $payment
     * @return array [token, redirect_url]
     * @throws \Exception
     */
    public function createSnapToken(Payment $payment): array
    {
        $booking = $payment->booking;
        $orderId = 'PAY-' . $payment->id . '-' . time(); // Unique order ID for Midtrans

        $itemName = ($payment->type === 'down_payment' ? 'DP 30%' : 'Pelunasan 70%') . ' - ' . ($booking->package->name ?? 'Sesi Foto');
        if (strlen($itemName) > 45) {
            $itemName = substr($itemName, 0, 42) . '...';
        }

        // Construct payload
        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $payment->amount,
            ],
            'customer_details' => [
                'first_name' => $booking->client_name,
                'email' => $booking->client_email,
                'phone' => $booking->client_phone,
            ],
            'item_details' => [
                [
                    'id' => 'pay-' . $payment->id,
                    'price' => (int) $payment->amount,
                    'quantity' => 1,
                    'name' => $itemName,
                ]
            ],
            // Enable QRIS, Bank Transfer, ShopeePay, Gopay, dll.
            'enabled_payments' => [
                'credit_card', 'mandiri_clickpay', 'cimb_clicks',
                'bca_klikbca', 'bca_klikpay', 'bri_epay', 'echannel',
                'bca_va', 'bni_va', 'bri_va', 'cimb_va', 'other_va',
                'gopay', 'shopeepay', 'indomaret', 'alfamart', 'qris'
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->withBasicAuth($this->serverKey, '')
            ->post($this->getSnapBaseUrl() . '/transactions', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Update transaction ID di table payments dengan order_id unik kita
                $payment->update([
                    'transaction_id' => $orderId,
                    'snap_token' => $data['token'] ?? null,
                ]);

                return [
                    'token' => $data['token'],
                    'redirect_url' => $data['redirect_url'],
                ];
            }

            Log::error('Midtrans API Request Failed: Status ' . $response->status() . ' - ' . $response->body());
            throw new \Exception('Gagal menghubungi Payment Gateway: ' . ($response->json()['error_messages'][0] ?? $response->body()));
        } catch (\Exception $e) {
            Log::error('Midtrans Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify Webhook Signature.
     *
     * @param array $payload
     * @return bool
     */
    public function verifySignature(array $payload): bool
    {
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        $input = $orderId . $statusCode . $grossAmount . $this->serverKey;
        $localSignature = hash('sha512', $input);

        return hash_equals($localSignature, $signatureKey);
    }
}
