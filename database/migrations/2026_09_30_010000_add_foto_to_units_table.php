<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Foto unit dipakai sebagai gambar utama story Instagram. Disimpan
        // sebagai nama file (bukan blob) supaya halaman admin tetap ringan
        // dan file-nya bisa dilayani langsung oleh web server.
        if (!Schema::hasColumn('units', 'foto')) {
            Schema::table('units', function (Blueprint $table) {
                $table->string('foto')->nullable()->after('kondisi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('units', 'foto')) {
            Schema::table('units', function (Blueprint $table) {
                $table->dropColumn('foto');
            });
        }
    }
};
