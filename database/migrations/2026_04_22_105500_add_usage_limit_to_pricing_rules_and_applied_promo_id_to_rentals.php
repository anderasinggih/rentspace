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
            $table->integer('usage_limit')->nullable()->after('syarat_tipe_durasi');
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->foreignId('applied_promo_id')->nullable()->after('applied_promo_name')->constrained('pricing_rules')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('applied_promo_id');
        });

        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropColumn('usage_limit');
        });
    }
};
