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
        $this->changeStatusEnum(['pending', 'paid', 'completed', 'cancelled', 'active', 'confirmed']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->changeStatusEnum(['pending', 'paid', 'completed', 'cancelled']);
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
