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
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->boolean('is_affiliate_only')->default(false);
            $table->boolean('requires_referral')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropColumn(['is_affiliate_only', 'requires_referral']);
        });
    }
};
