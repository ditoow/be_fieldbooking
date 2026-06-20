<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        Log::info('Midtrans Webhook Received. Payload: ' . json_encode($request->all()));
        try {
            $notification = $this->midtransService->handleNotification();
            
            $transactionStatus = $notification->transaction_status ?? null;
            $orderId = $notification->order_id ?? null;
            $fraudStatus = $notification->fraud_status ?? null;

            Log::info("Parsed Midtrans Notification: order_id={$orderId}, transaction_status={$transactionStatus}, fraud_status={$fraudStatus}");

            $booking = Booking::with(['schedules.field', 'user'])
                ->where('booking_number', $orderId)
                ->first();

            if (!$booking && $orderId) {
                $lastHyphenPos = strrpos($orderId, '-');
                if ($lastHyphenPos !== false) {
                    $baseOrderId = substr($orderId, 0, $lastHyphenPos);
                    $booking = Booking::with(['schedules.field', 'user'])
                        ->where('booking_number', $baseOrderId)
                        ->first();
                }
            }

            if (!$booking) {
                Log::warning("Midtrans Webhook: Booking not found for order_id={$orderId}");
                return response()->json(['message' => 'Booking not found'], 404);
            }

            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    Log::info("Approving booking ID {$booking->id} (capture-accept)");
                    $this->bookingService->approvePaidBooking($booking);
                }
            } else if ($transactionStatus == 'settlement') {
                Log::info("Approving booking ID {$booking->id} (settlement)");
                $this->bookingService->approvePaidBooking($booking);
            } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
                Log::info("Failing booking ID {$booking->id} (status={$transactionStatus})");
                $this->bookingService->failPaidBooking($booking, $transactionStatus);
            }

            return response()->json([
                'message' => 'Webhook handled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Midtrans Notification Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'message' => 'Internal server error'
            ], 500);
        }
    }

    public function simulateSuccess(Request $request): JsonResponse
    {
        $booking = Booking::with(['schedules.field', 'user'])->find($request->booking_id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking is not in pending status'], 422);
        }

        $this->bookingService->approvePaidBooking($booking);

        Log::info("Payment simulated for booking ID {$booking->id}");

        return response()->json([
            'message' => 'Payment simulated successfully',
            'data' => new \App\Http\Resources\BookingResource($booking->load(['schedules.field', 'user'])),
        ]);
    }
}
