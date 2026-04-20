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
        // 1. Affiliator Profiles linked to Users
        Schema::create('affiliator_profiles', function (Blueprint $col) {
            $col->id();
            $col->foreignId('user_id')->constrained()->onDelete('cascade');
            $col->string('nik')->unique();
            $col->string('no_hp');
            $col->text('alamat');
            $col->string('bank_name');
            $col->string('bank_account_number');
            $col->string('bank_account_name');
            $col->decimal('commission_rate', 5, 2)->default(0); // e.g. 5.00 for 5%
            $col->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $col->timestamps();
        });

        // 2. Affiliate Commissions (Earnings per rental)
        Schema::create('affiliate_commissions', function (Blueprint $col) {
            $col->id();
            $col->foreignId('affiliator_id')->constrained('users')->onDelete('cascade');
            $col->foreignId('rental_id')->constrained()->onDelete('cascade');
            $col->decimal('amount', 15, 2);
            $col->enum('status', ['earned', 'payout_pending', 'paid'])->default('earned');
            $col->timestamp('paid_at')->nullable();
            $col->timestamps();
        });

        // 3. Affiliate Payouts (Withdrawals)
        Schema::create('affiliate_payouts', function (Blueprint $col) {
            $col->id();
            $col->foreignId('affiliator_id')->constrained('users')->onDelete('cascade');
            $col->decimal('amount', 15, 2);
            $col->enum('status', ['pending', 'processed', 'rejected'])->default('pending');
            $col->string('proof_of_payment')->nullable(); // Image path
            $col->text('note')->nullable();
            $col->timestamps();
        });

        // 4. Update Rentals table
        Schema::table('rentals', function (Blueprint $col) {
            $col->string('affiliate_code')->nullable()->index();
            $col->foreignId('affiliator_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $col) {
            $col->dropForeign(['affiliator_id']);
            $col->dropColumn(['affiliate_code', 'affiliator_id']);
        });
        Schema::dropIfExists('affiliate_payouts');
        Schema::dropIfExists('affiliate_commissions');
        Schema::dropIfExists('affiliator_profiles');
    }
};
