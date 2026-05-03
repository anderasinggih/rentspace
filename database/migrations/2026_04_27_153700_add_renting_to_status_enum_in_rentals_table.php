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
        // Using raw SQL because changing ENUM via Schema Blueprint requires doctrine/dbal and is often problematic with ENUMs
        DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('pending', 'paid', 'renting', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('pending', 'paid', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
