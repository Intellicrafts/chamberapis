<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds anti-gaming / anti-spam columns to the existing `reviews` table.
     * Uses `Schema::table()` so existing data and indexes are untouched.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // IPv4 or IPv6 – max 45 chars covers full IPv6 notation
            $table->string('ip_address', 45)->nullable()->after('comment')
                  ->comment('IP address of the reviewer for duplicate/spam detection');

            // Device fingerprint supplied by the mobile/web app
            $table->string('device_id', 255)->nullable()->after('ip_address')
                  ->comment('Unique device identifier from the client app for anti-gaming');

            // ─── Index for quick duplicate lookups ──────────────────────────
            $table->index('ip_address');
            $table->index('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['ip_address']);
            $table->dropIndex(['device_id']);
            $table->dropColumn(['ip_address', 'device_id']);
        });
    }
};
