<?php

namespace App\Services;

use App\Models\Escrow;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EscrowService
{
    public function __construct(
        private WalletService $walletService
    ) {}

    public function createEscrow(Order $order, Transaction $transaction): Escrow
    {
        return DB::transaction(function () use ($order, $transaction) {
            $escrow = Escrow::create([
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'status' => 'held',
                'amount' => $order->amount,
                'held_at' => now(),
            ]);

            $this->walletService->hold($order->buyer, $order->amount);

            Log::info('Escrow created', [
                'order_id' => $order->id,
                'escrow_id' => $escrow->id,
                'amount' => $order->amount,
            ]);

            return $escrow;
        });
    }

    public function releaseToSeller(Order $order, User $releasedBy, string $reason = null): void
    {
        DB::transaction(function () use ($order, $releasedBy, $reason) {
            $escrow = $order->escrow;

            if (!$escrow || !$escrow->isHeld()) {
                throw new \Exception('Escrow not found or already processed');
            }

            $this->walletService->releaseHold($order->buyer, $escrow->amount);
            
            $sellerAmount = $order->seller_amount;
            $this->walletService->deposit(
                $order->seller,
                $sellerAmount,
                "Order #{$order->order_number} payment released"
            );

            $escrow->update([
                'status' => 'released',
                'released_at' => now(),
                'released_by' => $releasedBy->id,
                'release_reason' => $reason ?? 'Order completed',
            ]);

            Log::info('Escrow released to seller', [
                'order_id' => $order->id,
                'escrow_id' => $escrow->id,
                'seller_id' => $order->seller_id,
                'amount' => $sellerAmount,
            ]);
        });
    }

    public function refundToBuyer(Order $order, User $refundedBy, string $reason = null): void
    {
        DB::transaction(function () use ($order, $refundedBy, $reason) {
            $escrow = $order->escrow;

            if (!$escrow || !$escrow->isHeld()) {
                throw new \Exception('Escrow not found or already processed');
            }

            $this->walletService->releaseHold($order->buyer, $escrow->amount);
            $this->walletService->deposit(
                $order->buyer,
                $escrow->amount,
                "Order #{$order->order_number} refund"
            );

            $escrow->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'released_by' => $refundedBy->id,
                'release_reason' => $reason ?? 'Order refunded',
            ]);

            Log::info('Escrow refunded to buyer', [
                'order_id' => $order->id,
                'escrow_id' => $escrow->id,
                'buyer_id' => $order->buyer_id,
                'amount' => $escrow->amount,
            ]);
        });
    }

    public function autoRelease(Order $order): void
    {
        if (!$order->auto_complete_enabled) {
            return;
        }

        $hours = $order->auto_complete_hours ?? 72;
        $deliveredAt = $order->delivered_at;

        if (!$deliveredAt || $deliveredAt->addHours($hours)->isFuture()) {
            return;
        }

        $this->releaseToSeller($order, $order->seller, 'Auto-release after delivery period');
    }
}

