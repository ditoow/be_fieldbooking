<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Notification;
use Midtrans\Transaction;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$clientKey = config('services.midtrans.client_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized');
        Config::$is3ds = config('services.midtrans.is_3ds');
    }

    public function createQris(Booking $booking)
    {
        $expiryMinutes = max(1, now()->diffInMinutes($booking->expires_at));

        $params = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $booking->booking_number . '-' . Str::random(8),
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email' => $booking->user->email,
            ],
            'custom_expiry' => [
                'expiry_duration' => $expiryMinutes,
                'unit' => 'minute'
            ]
        ];

        try {
            $response = CoreApi::charge($params);

            $qrString = null;
            if (isset($response->qr_string)) {
                $qrString = $response->qr_string;
            }

            return [
                'transaction_id' => $response->transaction_id ?? null,
                'qr_string' => $qrString,
                'actions' => $response->actions ?? [],
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to create QRIS payment: ' . $e->getMessage());
        }
    }

    public function handleNotification(): Notification
    {
        $notification = new Notification();

        // Verifikasi signature manual (lapisan keamanan tambahan)
        $computedSignature = hash(
            'sha512',
            $notification->order_id . $notification->status_code . $notification->gross_amount . Config::$serverKey
        );

        if ($notification->signature_key !== $computedSignature) {
            Log::warning('Midtrans signature verification failed', [
                'expected' => $computedSignature,
                'received' => $notification->signature_key,
            ]);
            throw new \Exception('Signature verification failed.');
        }

        return $notification;
    }

    public function checkTransactionStatus(string $transactionId)
    {
        try {
            return Transaction::status($transactionId);
        } catch (\Exception $e) {
            Log::error('Failed to check Midtrans status directly: ' . $e->getMessage());
            return null;
        }
    }

    public function cancelTransaction(string $transactionId)
    {
        try {
            return Transaction::cancel($transactionId);
        } catch (\Exception $e) {
            Log::error('Failed to cancel Midtrans transaction directly: ' . $e->getMessage());
            return null;
        }
    }
}
