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
        // Many databases don't support modifying ENUM directly via Blueprint comfortably, 
        // so we use raw SQL for best compatibility.
        DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('pending', 'paid', 'completed', 'cancelled', 'active', 'confirmed') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('pending', 'paid', 'completed', 'cancelled') DEFAULT 'pending'");
    }
};
