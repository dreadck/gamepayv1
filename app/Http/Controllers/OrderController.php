<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = Order::query();

        if ($user->isBuyer() && !$user->isAdmin()) {
            $query->where('buyer_id', $user->id);
        } elseif ($user->isSeller() && !$user->isAdmin()) {
            $query->where('seller_id', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->with(['product', 'buyer', 'seller'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $user = auth()->user();

        if (!$user->isAdmin() && $order->buyer_id !== $user->id && $order->seller_id !== $user->id) {
            abort(403);
        }

        $order->load(['product', 'buyer', 'seller', 'escrow', 'dispute', 'review']);

        return view('orders.show', compact('order'));
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'delivery_data' => 'nullable|array',
        ]);

        try {
            $order = $this->orderService->createOrder(
                auth()->user(),
                $product,
                $validated['delivery_data'] ?? []
            );

            $transaction = $this->orderService->payOrder($order);

            return redirect()->route('orders.show', $order)
                ->with('success', __('Order created and paid successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function deliver(Request $request, Order $order)
    {
        $user = auth()->user();

        if ($order->seller_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        try {
            $this->orderService->deliverOrder($order, $request->all());
            return back()->with('success', __('Order delivered successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function complete(Order $order)
    {
        $user = auth()->user();

        if ($order->buyer_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        try {
            $this->orderService->completeOrder($order, $user);
            return back()->with('success', __('Order completed successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->orderService->cancelOrder($order, auth()->user(), $validated['reason']);
            return redirect()->route('orders.index')
                ->with('success', __('Order cancelled successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

