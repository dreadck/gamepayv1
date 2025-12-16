<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function createProduct(User $seller, array $data, array $translations, array $images = []): Product
    {
        return DB::transaction(function () use ($seller, $data, $translations, $images) {
            $data['seller_id'] = $seller->id;
            $data['status'] = 'pending';

            $product = Product::create($data);

            foreach ($translations as $locale => $translation) {
                $product->translations()->create([
                    'locale' => $locale,
                    ...$translation,
                ]);
            }

            foreach ($images as $index => $image) {
                $product->images()->create([
                    'path' => $image['path'],
                    'sort_order' => $index,
                    'is_primary' => $index === 0,
                ]);
            }

            Log::info('Product created', [
                'product_id' => $product->id,
                'seller_id' => $seller->id,
            ]);

            return $product;
        });
    }

    public function approveProduct(Product $product, User $admin): void
    {
        DB::transaction(function () use ($product, $admin) {
            $product->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $admin->id,
            ]);

            Log::info('Product approved', [
                'product_id' => $product->id,
                'admin_id' => $admin->id,
            ]);
        });
    }

    public function rejectProduct(Product $product, User $admin, string $reason): void
    {
        DB::transaction(function () use ($product, $admin, $reason) {
            $product->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            Log::info('Product rejected', [
                'product_id' => $product->id,
                'admin_id' => $admin->id,
                'reason' => $reason,
            ]);
        });
    }

    public function suspendProduct(Product $product, User $admin, string $reason = null): void
    {
        DB::transaction(function () use ($product, $admin, $reason) {
            $product->update([
                'status' => 'suspended',
                'rejection_reason' => $reason,
            ]);

            Log::info('Product suspended', [
                'product_id' => $product->id,
                'admin_id' => $admin->id,
            ]);
        });
    }

    public function updateRating(Product $product): void
    {
        $reviews = $product->reviews;
        
        if ($reviews->isEmpty()) {
            return;
        }

        $averageRating = $reviews->avg('rating');
        $reviewsCount = $reviews->count();

        $product->update([
            'rating' => round($averageRating, 2),
            'reviews_count' => $reviewsCount,
        ]);
    }
}

