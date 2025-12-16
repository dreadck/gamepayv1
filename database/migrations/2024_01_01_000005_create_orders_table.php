<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->enum('status', [
                'pending', 'paid', 'processing', 'delivered', 
                'completed', 'cancelled', 'refunded', 'disputed'
            ])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->decimal('commission', 10, 2)->default(0.00);
            $table->decimal('seller_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->text('delivery_data')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->boolean('auto_complete_enabled')->default(true);
            $table->integer('auto_complete_hours')->default(72);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['buyer_id', 'status']);
            $table->index(['seller_id', 'status']);
            $table->index(['product_id', 'status']);
            $table->index('order_number');
            $table->index('status');
        });

        Schema::create('escrows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->unique()->constrained()->onDelete('restrict');
            $table->enum('status', ['held', 'released', 'refunded'])->default('held');
            $table->decimal('amount', 10, 2);
            $table->timestamp('held_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('release_reason')->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrows');
        Schema::dropIfExists('orders');
    }
};

