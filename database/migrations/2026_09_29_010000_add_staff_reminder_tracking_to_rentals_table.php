<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda reminder karyawan per pesanan.
     *
     * Dipakai command `app:remind-staff` supaya tiap pengingat hanya dikirim
     * sekali. Tanpa penanda ini, scheduler yang jalan tiap 5 menit akan
     * mengirim pesan yang sama berulang-ulang ke grup.
     */
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->timestamp('reminder_pickup_sent_at')->nullable()->after('is_overdue_notified');
            $table->timestamp('reminder_return_sent_at')->nullable()->after('reminder_pickup_sent_at');
            $table->timestamp('alert_pickup_late_sent_at')->nullable()->after('reminder_return_sent_at');
            $table->timestamp('alert_return_late_sent_at')->nullable()->after('alert_pickup_late_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn([
                'reminder_pickup_sent_at',
                'reminder_return_sent_at',
                'alert_pickup_late_sent_at',
                'alert_return_late_sent_at',
            ]);
        });
    }
};
