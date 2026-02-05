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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('excerpt');
            $table->longText('image')->nullable(); // Changed to LONGTEXT for base64 images
            $table->string('category');
            $table->string('author')->nullable();
            $table->string('read_time')->default('5 min read');
            $table->boolean('is_prime')->default(false);
            $table->boolean('is_headline')->default(false);
            $table->string('status')->default('Draft'); // Added status field
            $table->json('meta_tags')->nullable();
            $table->text('meta_description')->nullable();
            $table->integer('seo_score')->nullable();
            $table->longText('content')->nullable(); // Changed to LONGTEXT for large content
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
