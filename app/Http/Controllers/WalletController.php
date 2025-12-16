<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {}

    public function index()
    {
        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);
        
        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('wallet.index', compact('wallet', 'transactions'));
    }

    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $transaction = $this->walletService->deposit(
                auth()->user(),
                $validated['amount'],
                $validated['description'] ?? null
            );

            return back()->with('success', __('Deposit successful.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $transaction = $this->walletService->withdraw(
                auth()->user(),
                $validated['amount'],
                $validated['description'] ?? null
            );

            return back()->with('success', __('Withdrawal request submitted. Waiting for approval.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

