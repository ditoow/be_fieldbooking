<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;

class PaymentService
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
        $this->configureMidtrans();
    }

    protected function configureMidtrans()
    {
        $serverKey = config('midtrans.server_key');

        if (empty($serverKey)) {
            return;
        }

        Config::$serverKey = $serverKey;
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production', false);
        Config::$isSanitized = config('midtrans.is_sanitized', true);
        Config::$is3ds = config('midtrans.is_3ds', true);
    }

    public function createPayment(Booking $booking)
    {
        $schedule = $booking->schedule;

        $orderId = 'BOOK-' . $booking->id . '-' . Str::upper(Str::random(8));

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'order_id' => $orderId,
            'amount' => $schedule->price,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
        ]);

        $snapToken = $this->getSnapToken($payment, $booking);

        return [
            'payment' => $payment,
            'snap_token' => $snapToken,
        ];
    }

    protected function getSnapToken(Payment $payment, Booking $booking)
    {
        $params = [
            'transaction_details' => [
                'order_id' => $payment->order_id,
                'gross_amount' => $payment->amount,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email' => $booking->user->email,
            ],
            'item_details' => [
                [
                    'id' => $booking->schedule_id,
                    'price' => $payment->amount,
                    'quantity' => 1,
                    'name' => 'Booking ' . $booking->schedule->field->name,
                ],
            ],
        ];

        return Snap::getSnapToken($params);
    }

    public function handleNotification($notification)
    {
        $orderId = $notification['order_id'];
        $transactionStatus = $notification['transaction_status'];
        $paymentType = $notification['payment_type'];
        $transactionId = $notification['transaction_id'];

        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            return ['error' => 'Payment not found'];
        }

        return DB::transaction(function () use ($payment, $notification, $transactionStatus, $paymentType, $transactionId) {
            switch ($transactionStatus) {
                case 'settlement':
                case 'capture':
                    $payment->update([
                        'status' => 'paid',
                        'transaction_id' => $transactionId,
                        'payment_type' => $paymentType,
                        'transaction_time' => now(),
                    ]);

                    $booking = $payment->booking;
                    $this->bookingService->approveBooking($booking);
                    break;

                case 'pending':
                    $payment->update([
                        'status' => 'pending',
                        'payment_type' => $paymentType,
                    ]);
                    break;

                case 'deny':
                case 'reject':
                    $payment->update(['status' => 'failed']);
                    break;

                case 'expire':
                    $payment->update(['status' => 'expired']);
                    $payment->booking->update(['status' => 'rejected']);
                    break;

                case 'cancel':
                    $payment->update(['status' => 'cancelled']);
                    break;
            }

            return $payment;
        });
    }

    public function getExpiredPayments()
    {
        return Payment::with('booking')
            ->pending()
            ->expired()
            ->get();
    }

    public function getPaymentByBooking(Booking $booking)
    {
        return Payment::where('booking_id', $booking->id)->first();
    }
}