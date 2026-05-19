<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;

class ExpirePaymentsCommand extends Command
{
    protected $signature = 'payments:expire';
    protected $description = 'Expire pending payments that have passed their expires_at time';

    public function handle()
    {
        $expiredPayments = Payment::with('booking')
            ->pending()
            ->expired()
            ->get();

        foreach ($expiredPayments as $payment) {
            $payment->update(['status' => 'expired']);

            if ($payment->booking) {
                $payment->booking->update(['status' => 'rejected']);
            }

            $this->info("Expired payment: {$payment->order_id}");
        }

        $this->info("Total expired payments: {$expiredPayments->count()}");
    }
}