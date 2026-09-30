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
        Schema::create('pinned_reports', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->string('group_jid')->index();
            $table->string('sender_phone')->nullable();
            $table->string('sender_name')->nullable();
            $table->text('message_text');
            $table->boolean('is_pinned')->default(true)->index();
            $table->timestamp('pinned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pinned_reports');
    }
};
