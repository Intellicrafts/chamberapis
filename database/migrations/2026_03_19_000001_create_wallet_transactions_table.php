<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add wallet balance columns to users table if they don't exist
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'wallet_balance')) {
                $table->decimal('wallet_balance', 12, 2)->default(0.00)->after('email');
            }
            if (!Schema::hasColumn('users', 'earned_balance')) {
                $table->decimal('earned_balance', 12, 2)->default(0.00)->after('wallet_balance');
            }
            if (!Schema::hasColumn('users', 'promotional_balance')) {
                $table->decimal('promotional_balance', 12, 2)->default(0.00)->after('earned_balance');
            }
        });

        // Create wallet_transactions table
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['CREDIT', 'DEBIT']);
            $table->decimal('amount', 12, 2);
            $table->string('category')->default('RECHARGE'); // RECHARGE, WITHDRAWAL, PAYMENT, BONUS
            $table->string('balance_type')->default('earned'); // earned, promotional
            $table->string('description')->nullable();
            $table->string('reference_id')->nullable(); // payment gateway ref
            $table->enum('status', ['PENDING', 'SUCCESS', 'FAILED'])->default('SUCCESS');
            $table->json('meta')->nullable(); // extra data
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wallet_balance', 'earned_balance', 'promotional_balance']);
        });
    }
};
