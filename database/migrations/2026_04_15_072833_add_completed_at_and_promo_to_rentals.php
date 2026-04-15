<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('updated_at');
            $table->string('applied_promo_name')->nullable()->after('potongan_diskon');
            $table->integer('hari_bonus')->default(0)->after('applied_promo_name');
            $table->integer('jam_bonus')->default(0)->after('hari_bonus');
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn(['completed_at', 'applied_promo_name', 'hari_bonus', 'jam_bonus']);
        });
    }
};
