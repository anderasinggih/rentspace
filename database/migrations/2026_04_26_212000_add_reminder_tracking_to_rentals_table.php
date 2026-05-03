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
        Schema::table('rentals', function (Blueprint $table) {
            $table->boolean('is_reminder_sent')->default(false)->after('is_admin_notified');
            $table->boolean('is_overdue_notified')->default(false)->after('is_reminder_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn(['is_reminder_sent', 'is_overdue_notified']);
        });
    }
};
