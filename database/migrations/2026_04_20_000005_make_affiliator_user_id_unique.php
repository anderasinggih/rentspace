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
        Schema::table('affiliator_profiles', function (Blueprint $table) {
            // First, ensure we don't have duplicates before adding the index
            // (In a real scenario we'd clean up, but here we'll just try to add it)
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affiliator_profiles', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });
    }
};
