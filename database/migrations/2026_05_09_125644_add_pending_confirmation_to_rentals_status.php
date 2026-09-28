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
        // DB::statement is used because change() doesn't support ENUM updates easily in all versions
        $this->changeStatusEnum(['pending', 'paid', 'renting', 'completed', 'cancelled', 'pending_confirmation']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->changeStatusEnum(['pending', 'paid', 'renting', 'completed', 'cancelled']);
    }

    /**
     * Ubah daftar nilai status.
     *
     * MySQL boleh pakai SQL mentah, driver lain (mis. sqlite untuk pengujian)
     * tidak mengenal "MODIFY COLUMN" sehingga harus lewat Schema Builder.
     */
    private function changeStatusEnum(array $values): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $list = implode(', ', array_map(fn ($v) => "'{$v}'", $values));
            DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM({$list}) DEFAULT 'pending'");

            return;
        }

        Schema::table('rentals', function (Blueprint $table) use ($values) {
            $table->enum('status', $values)->default('pending')->change();
        });
    }
};
