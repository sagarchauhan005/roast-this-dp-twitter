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
        Schema::create('processed_tweets', function (Blueprint $table) {
            $table->id();
            $table->string('tweet_id')->unique();
            $table->string('requester_username');
            $table->string('target_username');
            $table->text('roast_text')->nullable();
            $table->string('reply_tweet_id')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('tweet_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_tweets');
    }
};
