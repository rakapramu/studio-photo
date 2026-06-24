<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    protected MidtransService $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Helper to verify URL signature/hash.
     */
    protected function verifyHash(Booking $booking, string $hash): bool
    {
        return $hash === md5($booking->id . $booking->client_email . config('app.key'));
    }

    /**
     * Show Client Dashboard for Booking Details & Payment.
     */
    public function show(Booking $booking, string $hash): Response
    {
        if (!$this->verifyHash($booking, $hash)) {
            abort(403, 'Akses tidak sah (Invalid Signature).');
        }

        // Load relations
        $booking->load(['package', 'contract', 'payments']);

        return Inertia::render('Client/Payment', [
            'booking' => $booking,
            'hash' => $hash,
            'clientKey' => config('services.midtrans.client_key') ?? env('MIDTRANS_CLIENT_KEY', ''),
        ]);
    }

    /**
     * Initiate Payment snap token creation.
     */
    public function initiatePayment(Request $request, Booking $booking, string $hash): JsonResponse
    {
        if (!$this->verifyHash($booking, $hash)) {
            return response()->json(['error' => 'Akses tidak sah.'], 403);
        }

        $request->validate([
            'type' => ['required', 'in:down_payment,final_payment'],
        ]);

        $type = $request->type;
        $totalPrice = (float) $booking->total_price;

        // DP = 30%, Pelunasan = 70%
        $amount = $type === 'down_payment' ? $totalPrice * 0.3 : $totalPrice * 0.7;

        // Check if there is already a paid payment of this type
        $existingPaid = $booking->payments()->where('type', $type)->where('status', 'paid')->exists();
        if ($existingPaid) {
            return response()->json(['error' => 'Pembayaran jenis ini sudah lunas.'], 422);
        }

        // Check for pending payment to reuse snap token if exists
        $payment = $booking->payments()->where('type', $type)->where('status', 'pending')->first();

        try {
            DB::beginTransaction();

            if (!$payment) {
                $payment = Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $amount,
                    'status' => 'pending',
                    'type' => $type,
                ]);
            }

            // Request new Snap Token
            $snapData = $this->midtransService->createSnapToken($payment);

            DB::commit();

            return response()->json([
                'token' => $snapData['token'],
                'redirect_url' => $snapData['redirect_url'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle Midtrans HTTP Webhook Notification.
     */
    public function notification(Request $request): JsonResponse
    {
        $payload = $request->all();
        
        Log::info('Midtrans Notification Payload Received: ', $payload);

        // Verify Signature
        if (!$this->midtransService->verifySignature($payload)) {
            Log::warning('Midtrans Webhook Invalid Signature Key!');
            return response()->json(['error' => 'Invalid Signature'], 400);
        }

        $orderId = $payload['order_id'] ?? '';
        $transactionStatus = $payload['transaction_status'] ?? '';
        $fraudStatus = $payload['fraud_status'] ?? '';
        $paymentMethod = $payload['payment_type'] ?? '';

        // Extract payment ID from order_id (format: PAY-{paymentId}-{timestamp})
        $parts = explode('-', $orderId);
        $paymentId = $parts[1] ?? null;

        if (!$paymentId) {
            Log::error('Midtrans Webhook: payment ID not found in order_id ' . $orderId);
            return response()->json(['error' => 'Invalid Order ID'], 404);
        }

        $payment = Payment::find($paymentId);

        if (!$payment) {
            Log::error('Midtrans Webhook: Payment record not found in database: ' . $paymentId);
            return response()->json(['error' => 'Payment not found'], 404);
        }

        try {
            DB::transaction(function () use ($payment, $transactionStatus, $fraudStatus, $paymentMethod) {
                // Refresh lock
                $payment->lockForUpdate();
                $booking = $payment->booking()->lockForUpdate()->first();

                $oldStatus = $payment->status;
                $newStatus = 'pending';

                if ($transactionStatus === 'capture') {
                    if ($fraudStatus === 'accept') {
                        $newStatus = 'paid';
                    }
                } elseif ($transactionStatus === 'settlement') {
                    $newStatus = 'paid';
                } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                    $newStatus = 'failed';
                } elseif ($transactionStatus === 'expire') {
                    $newStatus = 'expired';
                }

                // Update status payment
                $payment->update([
                    'status' => $newStatus,
                    'payment_method' => $paymentMethod,
                ]);

                // Update booking paid amount if transitioned to paid
                if ($newStatus === 'paid' && $oldStatus !== 'paid') {
                    $booking->increment('paid_amount', $payment->amount);
                    Log::info("Booking ID {$booking->id}: Incrementing paid_amount by {$payment->amount}");

                    // If total paid matches or exceeds package price, optionally transition booking status
                    if ($booking->paid_amount >= $booking->total_price) {
                        Log::info("Booking ID {$booking->id}: Fully paid.");
                    }
                }
            });

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Midtrans Webhook Transaction Processing Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Simulate Webhook Payment Success for local testing.
     */
    public function simulateWebhook(Request $request, Booking $booking, string $hash): JsonResponse
    {
        if (config('app.env') !== 'local') {
            return response()->json(['error' => 'Hanya diizinkan pada environment local.'], 403);
        }

        if (!$this->verifyHash($booking, $hash)) {
            return response()->json(['error' => 'Akses tidak sah.'], 403);
        }

        $request->validate([
            'type' => ['required', 'in:down_payment,final_payment'],
        ]);

        $type = $request->type;
        $payment = $booking->payments()->where('type', $type)->first();

        if (!$payment) {
            return response()->json(['error' => 'Pembayaran belum diinisiasi. Silakan klik tombol bayar terlebih dahulu.'], 404);
        }

        if ($payment->status === 'paid') {
            return response()->json(['error' => 'Pembayaran ini sudah lunas.'], 422);
        }

        try {
            DB::transaction(function () use ($payment, $booking) {
                $payment->lockForUpdate();
                $booking->lockForUpdate()->first();

                $payment->update([
                    'status' => 'paid',
                    'payment_method' => 'qris_developer_mock',
                    'transaction_id' => 'MOCK-PAY-' . $payment->id . '-' . time(),
                ]);

                $booking->increment('paid_amount', $payment->amount);
            });

            return response()->json(['status' => 'success', 'message' => 'Simulasi pembayaran sukses berhasil diproses!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Simulasi gagal: ' . $e->getMessage()], 500);
        }
    }
}
