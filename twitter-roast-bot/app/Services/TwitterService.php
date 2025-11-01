<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TwitterService
{
    protected $apiKey;
    protected $apiSecret;
    protected $accessToken;
    protected $accessTokenSecret;
    protected $bearerToken;
    protected $botUsername;

    public function __construct()
    {
        $this->apiKey = config('services.twitter.api_key');
        $this->apiSecret = config('services.twitter.api_secret');
        $this->accessToken = config('services.twitter.access_token');
        $this->accessTokenSecret = config('services.twitter.access_token_secret');
        $this->bearerToken = config('services.twitter.bearer_token');
        $this->botUsername = config('services.twitter.bot_username');
    }

    /**
     * Get recent mentions of the bot
     */
    public function getRecentMentions($sinceId = null)
    {
        try {
            $url = 'https://api.twitter.com/2/tweets/search/recent';
            
            $params = [
                'query' => '@' . $this->botUsername,
                'max_results' => 10,
                'tweet.fields' => 'created_at,author_id,referenced_tweets',
                'expansions' => 'author_id,referenced_tweets.id',
                'user.fields' => 'username,profile_image_url',
            ];

            if ($sinceId) {
                $params['since_id'] = $sinceId;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
            ])->get($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Twitter API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching mentions: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user profile information including profile picture
     */
    public function getUserProfile($username)
    {
        try {
            $url = 'https://api.twitter.com/2/users/by/username/' . $username;
            
            $params = [
                'user.fields' => 'profile_image_url,description,name',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
            ])->get($url, $params);

            if ($response->successful()) {
                return $response->json()['data'] ?? null;
            }

            Log::error('Error fetching user profile', [
                'username' => $username,
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching user profile: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download profile picture from URL
     */
    public function downloadProfilePicture($profileImageUrl)
    {
        try {
            // Replace _normal with _400x400 for higher quality
            $highQualityUrl = str_replace('_normal', '_400x400', $profileImageUrl);
            
            $response = Http::get($highQualityUrl);

            if ($response->successful()) {
                return $response->body();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error downloading profile picture: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Post a reply to a tweet
     */
    public function postReply($tweetId, $text, $mentionedUsers = [])
    {
        try {
            $url = 'https://api.twitter.com/2/tweets';
            
            $data = [
                'text' => $text,
                'reply' => [
                    'in_reply_to_tweet_id' => $tweetId,
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
                'Content-Type' => 'application/json',
            ])->post($url, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error posting reply', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error posting reply: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse mentions to extract requester and target usernames
     */
    public function parseMention($tweetText)
    {
        // Extract all @mentions from the tweet
        preg_match_all('/@(\w+)/', $tweetText, $matches);
        
        $mentions = $matches[1] ?? [];
        
        // Remove bot's username
        $mentions = array_filter($mentions, function($username) {
            return strtolower($username) !== strtolower($this->botUsername);
        });

        // Reset array keys
        $mentions = array_values($mentions);

        return [
            'target' => $mentions[0] ?? null,
            'additional_mentions' => array_slice($mentions, 1),
        ];
    }

    /**
     * Build OAuth1.0a header for authenticated requests
     */
    protected function buildOAuthHeader($url, $method, $params = [])
    {
        $oauth = [
            'oauth_consumer_key' => $this->apiKey,
            'oauth_nonce' => md5(microtime() . mt_rand()),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0',
        ];

        $baseInfo = $this->buildBaseString($url, $method, array_merge($oauth, $params));
        $compositeKey = rawurlencode($this->apiSecret) . '&' . rawurlencode($this->accessTokenSecret);
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseInfo, $compositeKey, true));

        $header = 'OAuth ';
        $values = [];
        foreach ($oauth as $key => $value) {
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        }
        $header .= implode(', ', $values);

        return $header;
    }

    protected function buildBaseString($baseURI, $method, $params)
    {
        $r = [];
        ksort($params);
        foreach ($params as $key => $value) {
            $r[] = "$key=" . rawurlencode($value);
        }
        return $method . '&' . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
    }
}
