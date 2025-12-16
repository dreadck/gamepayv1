<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request)
    {
        $query = Product::with(['seller', 'category']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    public function show(Product $product)
    {
        $product->load(['seller', 'category', 'translations', 'images', 'attributes']);
        return view('admin.products.show', compact('product'));
    }

    public function approve(Product $product)
    {
        $this->productService->approveProduct($product, auth()->user());
        return back()->with('success', __('Product approved.'));
    }

    public function reject(Request $request, Product $product)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->productService->rejectProduct($product, auth()->user(), $validated['reason']);
        return back()->with('success', __('Product rejected.'));
    }

    public function suspend(Request $request, Product $product)
    {
        $reason = $request->input('reason');
        $this->productService->suspendProduct($product, auth()->user(), $reason);
        return back()->with('success', __('Product suspended.'));
    }
}

