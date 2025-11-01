<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedTweet extends Model
{
    protected $fillable = [
        'tweet_id',
        'requester_username',
        'target_username',
        'roast_text',
        'reply_tweet_id',
        'status',
        'error_message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
