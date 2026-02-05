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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('type'); // Leaderboard, MREC, Banner
            $table->string('status')->default('Scheduled'); // Active, Paused, Scheduled
            $table->decimal('price', 10, 2)->nullable();
            $table->string('invoice')->nullable();
            $table->longText('image')->nullable(); // LONGTEXT for base64 images
            $table->text('target_url')->nullable(); // TEXT for longer URLs
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('impressions')->default('0');
            $table->string('clicks')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
