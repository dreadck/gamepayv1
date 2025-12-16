<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    public function getOrCreateWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0.00,
                'pending_balance' => 0.00,
                'currency' => 'USD',
            ]
        );
    }

    public function deposit(User $user, float $amount, string $description = null, array $metadata = []): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $metadata) {
            $wallet = $this->getOrCreateWallet($user);
            
            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->increment('balance', $amount);

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'deposit',
                'status' => 'completed',
                'amount' => $amount,
                'fee' => 0.00,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'description' => $description ?? 'Balance top-up',
                'metadata' => $metadata,
                'processed_at' => now(),
            ]);

            Log::info('Wallet deposit', [
                'user_id' => $user->id,
                'amount' => $amount,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    public function withdraw(User $user, float $amount, string $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $wallet = $this->getOrCreateWallet($user);

            if (!$wallet->canWithdraw($amount)) {
                throw new \Exception('Insufficient balance');
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore - $amount;

            $wallet->decrement('balance', $amount);

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'withdrawal',
                'status' => 'pending',
                'amount' => $amount,
                'fee' => 0.00,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'description' => $description ?? 'Withdrawal request',
            ]);

            Log::info('Wallet withdrawal request', [
                'user_id' => $user->id,
                'amount' => $amount,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    public function approveWithdrawal(Transaction $transaction, User $admin): void
    {
        DB::transaction(function () use ($transaction, $admin) {
            if ($transaction->type !== 'withdrawal' || $transaction->status !== 'pending') {
                throw new \Exception('Invalid transaction');
            }

            $transaction->update([
                'status' => 'completed',
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            Log::info('Withdrawal approved', [
                'transaction_id' => $transaction->id,
                'admin_id' => $admin->id,
            ]);
        });
    }

    public function hold(User $user, float $amount): void
    {
        $wallet = $this->getOrCreateWallet($user);
        $wallet->increment('pending_balance', $amount);
    }

    public function releaseHold(User $user, float $amount): void
    {
        $wallet = $this->getOrCreateWallet($user);
        $wallet->decrement('pending_balance', $amount);
    }

    public function getBalance(User $user): float
    {
        $wallet = $this->getOrCreateWallet($user);
        return (float) $wallet->balance;
    }

    public function getAvailableBalance(User $user): float
    {
        $wallet = $this->getOrCreateWallet($user);
        return (float) $wallet->available_balance;
    }
}

