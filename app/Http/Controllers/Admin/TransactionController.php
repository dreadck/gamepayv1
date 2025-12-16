<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\WalletService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {}

    public function index(Request $request)
    {
        $query = Transaction::with(['user', 'wallet']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.transactions.index', compact('transactions'));
    }

    public function approveWithdrawal(Transaction $transaction)
    {
        try {
            $this->walletService->approveWithdrawal($transaction, auth()->user());
            return back()->with('success', __('Withdrawal approved.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

