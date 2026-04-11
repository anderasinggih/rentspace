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
        Schema::table('units', function (Blueprint $table) {
            $table->string('kategori')->default('iphone')->after('id');
            $table->string('imei')->nullable()->change();
            $table->string('memori')->nullable()->change();
            $table->string('warna')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('kategori');
            $table->string('imei')->nullable(false)->change();
            $table->string('memori')->nullable(false)->change();
            $table->string('warna')->nullable(false)->change();
        });
    }
};
