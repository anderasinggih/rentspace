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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('nama_promo');
            $table->string('tipe'); // diskon_persen, hari_gratis, fix_price
            $table->decimal('value', 10, 2);
            $table->integer('syarat_minimal_durasi')->nullable();
            $table->string('syarat_tipe_durasi')->default('jam');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
