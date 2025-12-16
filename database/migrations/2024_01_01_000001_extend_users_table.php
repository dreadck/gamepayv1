<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['buyer', 'seller', 'admin'])->default('buyer')->after('email');
            $table->string('username')->unique()->nullable()->after('name');
            $table->string('avatar')->nullable()->after('username');
            $table->decimal('rating', 3, 2)->default(0.00)->after('avatar');
            $table->integer('reputation')->default(0)->after('rating');
            $table->boolean('is_banned')->default(false)->after('reputation');
            $table->boolean('is_frozen')->default(false)->after('is_banned');
            $table->timestamp('banned_at')->nullable()->after('is_frozen');
            $table->text('ban_reason')->nullable()->after('banned_at');
            $table->timestamp('last_activity_at')->nullable()->after('ban_reason');
            $table->string('phone')->nullable()->after('last_activity_at');
            $table->text('bio')->nullable()->after('phone');
            $table->json('settings')->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 'username', 'avatar', 'rating', 'reputation',
                'is_banned', 'is_frozen', 'banned_at', 'ban_reason',
                'last_activity_at', 'phone', 'bio', 'settings'
            ]);
        });
    }
};

