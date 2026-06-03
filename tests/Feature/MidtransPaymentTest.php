<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Memverifikasi sukses pembuatan booking QRIS
     */
    public function test_grup_umum_dapat_membuat_booking_dengan_qr(): void
    {
        // 1. Mock MidtransService agar mengembalikan data buatan
        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldReceive('createQris')
                ->once()
                ->andReturn([
                    'transaction_id' => 'mocked-transaction-id-123',
                    'qr_string' => 'mocked-qr-string-123',
                    'actions' => []
                ]);
        });

        $user = User::whereHas('roles', function ($q) {
            $q->where('name', 'umum');
        })->first();

        $schedules = Schedule::where('status', 'available')->limit(2)->get();

        $response = $this->actingAs($user, 'api')->postJson('/api/bookings', [
            'schedule_ids' => $schedules->pluck('id')->toArray()
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'booking_number',
                'status',
                'booking_type',
                'total_price',
                'qr_id',
                'qr_string',
                'schedules'
            ]
        ]);

        $bookingId = $response->json('data.id');
        $booking = Booking::find($bookingId);

        $this->assertEquals('pending', $booking->status);
        $this->assertEquals('mocked-transaction-id-123', $booking->qr_id);
        $this->assertEquals('mocked-qr-string-123', $booking->qr_string);
    }

    /**
     * Memverifikasi pemrosesan sukses webhook notifikasi pembayaran
     */
    public function test_webhook_midtrans_sukses_mengubah_status_booking_menjadi_approved(): void
    {
        $user = User::whereHas('roles', function ($q) {
            $q->where('name', 'umum');
        })->first();

        $schedule = Schedule::where('status', 'available')->first();

        $booking = Booking::create([
            'user_id' => $user->id,
            'booking_number' => '#UGO-PAYTEST',
            'status' => 'pending',
            'booking_type' => 'paid',
            'total_price' => $schedule->price,
            'expires_at' => now()->addMinutes(30),
            'qr_id' => 'mocked-qr-id',
            'qr_string' => 'mocked-qr-string',
        ]);

        $booking->schedules()->attach($schedule->id);

        // Mock method handleNotification untuk menyimulasikan data sukses pembayaran
        $this->mock(MidtransService::class, function ($mock) use ($booking) {
            $notification = new \stdClass();
            $notification->transaction_status = 'settlement';
            $notification->order_id = $booking->booking_number;
            $notification->fraud_status = 'accept';

            $mock->shouldReceive('handleNotification')
                ->once()
                ->andReturn($notification);
        });

        $payload = [
            'order_id' => $booking->booking_number,
            'transaction_status' => 'settlement',
            'gross_amount' => $booking->total_price,
            'signature_key' => 'mocked-signature-key'
        ];

        $response = $this->postJson('/api/payment/midtrans-callback', $payload);

        $response->assertStatus(200);

        $booking->refresh();
        $this->assertEquals('approved', $booking->status);
        $this->assertNull($booking->expires_at);
    }
}
