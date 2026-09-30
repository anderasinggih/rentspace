<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Riwayat story yang sudah dipublish. Instagram tidak menyediakan
        // "daftar story yang gagal" lewat API, jadi kalau proses publish
        // setengah jalan (container jadi tapi publish error) kita butuh
        // catatan sendiri supaya admin tahu mana yang sudah tayang.
        Schema::create('instagram_story_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->enum('status', ['published', 'failed'])->default('failed');
            $table->text('caption')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_url')->nullable();
            $table->string('media_id')->nullable();
            $table->text('error_message')->nullable();
            $table->json('insights')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_story_posts');
    }
};
