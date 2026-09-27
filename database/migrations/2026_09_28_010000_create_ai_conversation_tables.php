<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel memori AI.
     *
     * Tujuannya: history chat tidak hilang saat cache kedaluwarsa / bot restart,
     * tapi tetap hemat token. Yang disimpan bukan seluruh history mentah, melainkan
     * "fakta" (kode booking, nama, status, catatan tim) + N turn terakhir.
     */
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 24); // wa_customer | wa_group_report | web_chat
            $table->string('peer_key', 64)->index(); // md5(jid/phone) agar tidak bocor ke prompt
            $table->string('peer_name', 80)->nullable();
            $table->string('title', 120)->nullable();

            // Memori durable: hasil ekstraksi deterministik (bukan panggilan LLM tambahan)
            // {"focus":[{...}],"people":{...},"notes":[{...}],"topics":[...]}
            $table->json('memory')->nullable();

            $table->unsignedInteger('turn_count')->default(0);
            $table->unsignedInteger('input_tokens')->default(0); // akumulasi token input terpakai
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'peer_key']);
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('role', 10); // user | model
            $table->string('actor', 80)->nullable(); // siapa yang bertanya (grup report)
            $table->text('content');
            $table->string('intent', 32)->nullable();
            $table->unsignedSmallInteger('input_tokens')->default(0); // estimasi token prompt saat giliran ini
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ai_conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
