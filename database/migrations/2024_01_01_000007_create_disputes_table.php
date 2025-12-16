<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->string('dispute_number')->unique();
            $table->foreignId('order_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('opened_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['open', 'in_review', 'resolved', 'closed'])->default('open');
            $table->enum('type', ['buyer', 'seller'])->default('buyer');
            $table->text('reason');
            $table->text('description');
            $table->enum('resolution', ['buyer_favor', 'seller_favor', 'partial_refund', 'full_refund', 'dismissed'])->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'status']);
            $table->index(['opened_by', 'status']);
            $table->index('status');
        });

        Schema::create('dispute_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['dispute_id', 'created_at']);
        });

        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('user_type', ['buyer', 'seller', 'admin'])->default('buyer');
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
            
            $table->index(['dispute_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_messages');
        Schema::dropIfExists('dispute_evidences');
        Schema::dropIfExists('disputes');
    }
};

