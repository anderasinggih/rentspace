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
        Schema::create('rental_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->decimal('price_snapshot', 15, 2)->comment('Price at the time of booking');
            $table->timestamps();
        });

        // Migrate existing data
        $rentals = DB::table('rentals')->get();
        foreach ($rentals as $rental) {
            DB::table('rental_items')->insert([
                'rental_id' => $rental->id,
                'unit_id' => $rental->unit_id,
                'price_snapshot' => $rental->subtotal_harga,
                'created_at' => $rental->created_at,
                'updated_at' => $rental->updated_at,
            ]);
        }

        // Make unit_id nullable on rentals (deprecated)
        Schema::table('rentals', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable(false)->change();
        });
        Schema::dropIfExists('rental_items');
    }
};
