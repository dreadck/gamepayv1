<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request)
    {
        $query = Product::where('status', 'approved')
            ->with(['seller', 'category', 'primaryImage']);

        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(24);

        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function show(Product $product)
    {
        if ($product->status !== 'approved') {
            abort(404);
        }

        $product->load(['seller', 'category', 'images', 'attributes', 'reviews.reviewer']);

        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'approved')
            ->limit(4)
            ->get();

        return view('products.show', compact('product', 'relatedProducts'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'slug' => 'required|string|max:255|unique:products',
            'type' => 'required|in:digital,service',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_instant_delivery' => 'boolean',
            'delivery_time_hours' => 'nullable|integer|min:1',
            'title_ru' => 'required|string|max:255',
            'title_uz' => 'required|string|max:255',
            'description_ru' => 'required|string',
            'description_uz' => 'required|string',
            'images.*' => 'image|max:2048',
        ]);

        $translations = [
            'ru' => [
                'title' => $validated['title_ru'],
                'description' => $validated['description_ru'],
            ],
            'uz' => [
                'title' => $validated['title_uz'],
                'description' => $validated['description_uz'],
            ],
        ];

        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                $images[] = ['path' => $path];
            }
        }

        $product = $this->productService->createProduct(
            auth()->user(),
            $validated,
            $translations,
            $images
        );

        return redirect()->route('products.show', $product)
            ->with('success', __('Product created successfully. Waiting for approval.'));
    }
}

