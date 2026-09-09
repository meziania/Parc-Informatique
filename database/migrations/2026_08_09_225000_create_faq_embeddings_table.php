<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_article_id')->constrained('faq_articles')->cascadeOnDelete();
            $table->string('model', 120);
            $table->json('embedding');
            $table->string('content_hash', 64);
            $table->timestamps();

            $table->unique('faq_article_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_embeddings');
    }
};
