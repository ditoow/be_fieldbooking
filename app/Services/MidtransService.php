<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Notification;
use Midtrans\Transaction;
use App\Models\Booking;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
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
                'order_id' => $booking->booking_number . '-' . time(),
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
            throw new \Exception('Gagal membuat pembayaran QRIS Midtrans: ' . $e->getMessage());
        }
    }

    public function handleNotification()
    {
        return new Notification();
    }

    public function checkTransactionStatus($transactionId)
    {
        try {
            return Transaction::status($transactionId);
        } catch (\Exception $e) {
            \Log::error('Gagal mengecek status Midtrans secara langsung: ' . $e->getMessage());
            return null;
        }
    }

    public function cancelTransaction($transactionId)
    {
        try {
            return Transaction::cancel($transactionId);
        } catch (\Exception $e) {
            \Log::error('Gagal membatalkan transaksi Midtrans secara langsung: ' . $e->getMessage());
            return null;
        }
    }
}
