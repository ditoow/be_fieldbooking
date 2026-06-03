<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingService;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected MidtransService $midtransService;
    protected BookingService $bookingService;

    public function __construct(MidtransService $midtransService, BookingService $bookingService)
    {
        $this->midtransService = $midtransService;
        $this->bookingService = $bookingService;
    }

    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            // Parsing payload dari request masuk melalui Midtrans SDK
            $notification = $this->midtransService->handleNotification();
            
            $transactionStatus = $notification->transaction_status;
            $orderId = $notification->order_id;
            $fraudStatus = $notification->fraud_status;

            // Cari data booking yang sesuai dengan nomor pesanan
            $booking = Booking::with(['schedules.field', 'user'])
                ->where('booking_number', $orderId)
                ->first();

            if (!$booking) {
                return response()->json(['message' => 'Booking not found'], 404);
            }

            // Tentukan status pembayaran dari Midtrans
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    $this->bookingService->approvePaidBooking($booking);
                }
            } else if ($transactionStatus == 'settlement') {
                $this->bookingService->approvePaidBooking($booking);
            } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
                $this->bookingService->failPaidBooking($booking, $transactionStatus);
            }

            return response()->json([
                'message' => 'Webhook handled successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Midtrans Notification Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Internal server error'
            ], 500);
        }
    }
}
