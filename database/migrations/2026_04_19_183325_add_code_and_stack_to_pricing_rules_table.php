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
            $table->string('kode_promo')->nullable()->after('nama_promo');
            $table->boolean('is_hidden')->default(false)->after('kode_promo');
            $table->boolean('can_stack')->default(false)->after('is_hidden');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropColumn(['kode_promo', 'is_hidden', 'can_stack']);
        });
    }
};
