<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\EscrowService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private WalletService $walletService,
        private EscrowService $escrowService
    ) {}

    public function createOrder(User $buyer, Product $product, array $deliveryData = []): Order
    {
        return DB::transaction(function () use ($buyer, $product, $deliveryData) {
            if (!$product->isAvailable()) {
                throw new \Exception('Product is not available');
            }

            if ($buyer->id === $product->seller_id) {
                throw new \Exception('Cannot purchase your own product');
            }

            $commissionRate = (float) \App\Models\Setting::get('commission_rate', 5.0);
            $commission = ($product->price * $commissionRate) / 100;
            $sellerAmount = $product->price - $commission;

            $order = Order::create([
                'buyer_id' => $buyer->id,
                'seller_id' => $product->seller_id,
                'product_id' => $product->id,
                'status' => 'pending',
                'amount' => $product->price,
                'commission' => $commission,
                'seller_amount' => $sellerAmount,
                'currency' => 'USD',
                'delivery_data' => $deliveryData,
                'auto_complete_enabled' => true,
                'auto_complete_hours' => 72,
            ]);

            if ($product->type === 'digital') {
                $product->decrement('stock');
            }

            Log::info('Order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'buyer_id' => $buyer->id,
                'product_id' => $product->id,
                'amount' => $order->amount,
            ]);

            return $order;
        });
    }

    public function payOrder(Order $order): Transaction
    {
        return DB::transaction(function () use ($order) {
            if ($order->status !== 'pending') {
                throw new \Exception('Order cannot be paid');
            }

            $buyerBalance = $this->walletService->getBalance($order->buyer);

            if ($buyerBalance < $order->amount) {
                throw new \Exception('Insufficient balance');
            }

            $wallet = $this->walletService->getOrCreateWallet($order->buyer);
            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore - $order->amount;

            $wallet->decrement('balance', $order->amount);

            $transaction = Transaction::create([
                'user_id' => $order->buyer_id,
                'wallet_id' => $wallet->id,
                'type' => 'order_payment',
                'status' => 'completed',
                'amount' => -$order->amount,
                'fee' => 0.00,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $order->currency,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'description' => "Payment for order #{$order->order_number}",
                'processed_at' => now(),
            ]);

            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $this->escrowService->createEscrow($order, $transaction);

            Log::info('Order paid', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    public function deliverOrder(Order $order, array $deliveryData = []): void
    {
        DB::transaction(function () use ($order, $deliveryData) {
            if ($order->status !== 'paid' && $order->status !== 'processing') {
                throw new \Exception('Order cannot be delivered');
            }

            $order->update([
                'status' => 'delivered',
                'delivered_at' => now(),
                'delivery_data' => array_merge($order->delivery_data ?? [], $deliveryData),
            ]);

            Log::info('Order delivered', [
                'order_id' => $order->id,
            ]);
        });
    }

    public function completeOrder(Order $order, User $completedBy = null): void
    {
        DB::transaction(function () use ($order, $completedBy) {
            if ($order->status !== 'delivered') {
                throw new \Exception('Order must be delivered first');
            }

            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->escrowService->releaseToSeller(
                $order,
                $completedBy ?? $order->buyer,
                'Order completed by buyer'
            );

            Log::info('Order completed', [
                'order_id' => $order->id,
            ]);
        });
    }

    public function cancelOrder(Order $order, User $cancelledBy, string $reason): void
    {
        DB::transaction(function () use ($order, $cancelledBy, $reason) {
            if (!$order->canBeCancelled()) {
                throw new \Exception('Order cannot be cancelled');
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            if ($order->isPaid()) {
                $this->escrowService->refundToBuyer(
                    $order,
                    $cancelledBy,
                    "Order cancelled: {$reason}"
                );
            }

            if ($order->product->type === 'digital') {
                $order->product->increment('stock');
            }

            Log::info('Order cancelled', [
                'order_id' => $order->id,
                'reason' => $reason,
            ]);
        });
    }

    public function refundOrder(Order $order, User $refundedBy, string $reason): void
    {
        DB::transaction(function () use ($order, $refundedBy, $reason) {
            if (!$order->isPaid()) {
                throw new \Exception('Order is not paid');
            }

            $order->update([
                'status' => 'refunded',
                'cancellation_reason' => $reason,
            ]);

            $this->escrowService->refundToBuyer($order, $refundedBy, $reason);

            if ($order->product->type === 'digital') {
                $order->product->increment('stock');
            }

            Log::info('Order refunded', [
                'order_id' => $order->id,
                'reason' => $reason,
            ]);
        });
    }
}

