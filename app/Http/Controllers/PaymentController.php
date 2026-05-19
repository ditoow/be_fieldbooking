<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function initiate(Request $request, $bookingId)
    {
        $booking = Booking::with(['schedule.field', 'user', 'payment'])
            ->where('id', $bookingId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($booking->booking_type !== 'paid') {
            return response()->json(['message' => 'This booking does not require payment'], 400);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking is not in pending status'], 400);
        }

        if ($booking->payment && $booking->payment->status === 'paid') {
            return response()->json(['message' => 'Payment already completed'], 400);
        }

        $existingPayment = $this->paymentService->getPaymentByBooking($booking);

        if ($existingPayment && $existingPayment->status === 'pending' && $existingPayment->expires_at > now()) {
            return response()->json([
                'payment' => $existingPayment,
                'snap_token' => $this->getSnapTokenForExistingPayment($existingPayment, $booking),
            ]);
        }

        try {
            $result = $this->paymentService->createPayment($booking);

            return response()->json([
                'message' => 'Payment initiated successfully',
                'payment' => $result['payment'],
                'snap_token' => $result['snap_token'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    protected function getSnapTokenForExistingPayment($payment, $booking)
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
        ];

        \Midtrans\Snap::getSnapToken($params);
    }

    public function callback(Request $request)
    {
        $notification = $request->all();

        $result = $this->paymentService->handleNotification($notification);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function status($bookingId)
    {
        $booking = Booking::with(['schedule.field', 'user', 'payment'])
            ->where('id', $bookingId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $payment = $booking->payment;

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        return response()->json([
            'booking' => $booking,
            'payment' => $payment,
        ]);
    }
}