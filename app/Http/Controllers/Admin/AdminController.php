<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Dispute;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'users' => User::count(),
            'products' => Product::count(),
            'orders' => Order::count(),
            'disputes' => Dispute::where('status', 'open')->count(),
            'pending_withdrawals' => Transaction::where('type', 'withdrawal')
                ->where('status', 'pending')
                ->count(),
            'pending_products' => Product::where('status', 'pending')->count(),
        ];

        $recentOrders = Order::with(['buyer', 'seller', 'product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders'));
    }
}

