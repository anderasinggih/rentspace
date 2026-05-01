<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('status');
        });

        // Backfill existing paid/renting/completed rentals
        DB::table('rentals')
            ->whereIn('status', ['paid', 'renting', 'completed'])
            ->whereNull('paid_at')
            ->update(['paid_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });
    }
};
