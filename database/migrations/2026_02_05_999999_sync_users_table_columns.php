<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) $table->string('phone')->nullable();
            if (!Schema::hasColumn('users', 'address')) $table->string('address')->nullable();
            if (!Schema::hasColumn('users', 'city')) $table->string('city')->nullable();
            if (!Schema::hasColumn('users', 'state')) $table->string('state')->nullable();
            if (!Schema::hasColumn('users', 'country')) $table->string('country')->nullable();
            if (!Schema::hasColumn('users', 'zip_code')) $table->string('zip_code')->nullable();
            if (!Schema::hasColumn('users', 'active')) $table->boolean('active')->default(true);
            if (!Schema::hasColumn('users', 'is_verified')) $table->boolean('is_verified')->default(false);
            if (!Schema::hasColumn('users', 'user_type')) $table->integer('user_type')->default(1);
            if (!Schema::hasColumn('users', 'deleted')) $table->boolean('deleted')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address', 'city', 'state', 'country', 'zip_code', 'active', 'is_verified', 'user_type', 'deleted']);
        });
    }
};
