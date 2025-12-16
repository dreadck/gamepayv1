<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function store(Request $request, Order $order)
    {
        $user = auth()->user();

        if ($order->buyer_id !== $user->id) {
            abort(403);
        }

        if ($order->review) {
            return back()->with('error', __('Review already exists for this order.'));
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = Review::create([
            'order_id' => $order->id,
            'reviewer_id' => $user->id,
            'reviewed_id' => $order->seller_id,
            'product_id' => $order->product_id,
            'type' => 'buyer_to_seller',
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'is_verified_purchase' => true,
            'is_visible' => true,
        ]);

        // Update product rating
        $this->productService->updateRating($order->product);

        // Update seller rating
        $seller = $order->seller;
        $sellerReviews = Review::where('reviewed_id', $seller->id)
            ->where('is_visible', true)
            ->get();
        
        if ($sellerReviews->isNotEmpty()) {
            $seller->update([
                'rating' => round($sellerReviews->avg('rating'), 2),
            ]);
        }

        return back()->with('success', __('Review submitted successfully.'));
    }
}

