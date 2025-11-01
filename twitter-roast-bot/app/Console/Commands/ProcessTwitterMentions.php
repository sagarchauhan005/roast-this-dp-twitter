<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProcessedTweet;
use App\Services\TwitterService;
use App\Services\LLMService;
use Illuminate\Support\Facades\Log;

class ProcessTwitterMentions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitter:process-mentions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Twitter mentions and roast profile pictures';

    protected $twitterService;
    protected $llmService;

    /**
     * Create a new command instance.
     */
    public function __construct(TwitterService $twitterService, LLMService $llmService)
    {
        parent::__construct();
        $this->twitterService = $twitterService;
        $this->llmService = $llmService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to process Twitter mentions...');

        try {
            // Get the last processed tweet ID to avoid processing duplicates
            $lastProcessed = ProcessedTweet::orderBy('created_at', 'desc')->first();
            $sinceId = $lastProcessed ? $lastProcessed->tweet_id : null;

            // Fetch recent mentions
            $mentionsData = $this->twitterService->getRecentMentions($sinceId);

            if (!$mentionsData || empty($mentionsData['data'])) {
                $this->info('No new mentions found.');
                return 0;
            }

            $tweets = $mentionsData['data'];
            $includes = $mentionsData['includes'] ?? [];
            $users = $includes['users'] ?? [];

            // Create a map of user IDs to user data
            $userMap = [];
            foreach ($users as $user) {
                $userMap[$user['id']] = $user;
            }

            $this->info('Found ' . count($tweets) . ' new mention(s).');

            foreach ($tweets as $tweet) {
                $this->processTweet($tweet, $userMap);
            }

            $this->info('Finished processing mentions.');
            return 0;

        } catch (\Exception $e) {
            $this->error('Error processing mentions: ' . $e->getMessage());
            Log::error('Process mentions error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Process a single tweet mention
     */
    protected function processTweet($tweet, $userMap)
    {
        try {
            $tweetId = $tweet['id'];
            $tweetText = $tweet['text'];
            $authorId = $tweet['author_id'];

            // Check if already processed
            if (ProcessedTweet::where('tweet_id', $tweetId)->exists()) {
                $this->info("Tweet {$tweetId} already processed. Skipping...");
                return;
            }

            // Get requester info
            $requester = $userMap[$authorId] ?? null;
            if (!$requester) {
                $this->warn("Could not find requester info for tweet {$tweetId}");
                return;
            }

            $requesterUsername = $requester['username'];

            // Parse the mention to get target username
            $parsed = $this->twitterService->parseMention($tweetText);
            $targetUsername = $parsed['target'];

            if (!$targetUsername) {
                $this->warn("No target username found in tweet {$tweetId}");
                
                // Reply to let user know
                $this->twitterService->postReply(
                    $tweetId,
                    "@{$requesterUsername} Please tag someone whose profile picture you want me to roast! ??\n\nExample: @roast_this_dp @username"
                );
                
                return;
            }

            $this->info("Processing: @{$requesterUsername} wants to roast @{$targetUsername}");

            // Create record
            $processedTweet = ProcessedTweet::create([
                'tweet_id' => $tweetId,
                'requester_username' => $requesterUsername,
                'target_username' => $targetUsername,
                'status' => 'processing',
            ]);

            // Get target user's profile
            $targetProfile = $this->twitterService->getUserProfile($targetUsername);

            if (!$targetProfile || !isset($targetProfile['profile_image_url'])) {
                throw new \Exception("Could not fetch profile for @{$targetUsername}");
            }

            // Download profile picture
            $profileImageUrl = $targetProfile['profile_image_url'];
            $imageData = $this->twitterService->downloadProfilePicture($profileImageUrl);

            if (!$imageData) {
                throw new \Exception("Could not download profile picture");
            }

            $this->info("Downloaded profile picture for @{$targetUsername}");

            // Generate roast
            $this->info("Generating roast...");
            $roast = $this->llmService->roastProfilePicture($imageData, $targetUsername);

            $this->info("Generated roast: {$roast}");

            // Post reply
            $replyText = "@{$requesterUsername} @{$targetUsername} {$roast}";
            
            $replyResponse = $this->twitterService->postReply($tweetId, $replyText);

            if ($replyResponse && isset($replyResponse['data']['id'])) {
                $replyTweetId = $replyResponse['data']['id'];
                
                $processedTweet->update([
                    'roast_text' => $roast,
                    'reply_tweet_id' => $replyTweetId,
                    'status' => 'completed',
                ]);

                $this->info("? Successfully posted roast! Reply ID: {$replyTweetId}");
            } else {
                throw new \Exception("Failed to post reply");
            }

        } catch (\Exception $e) {
            $this->error("Error processing tweet {$tweetId}: " . $e->getMessage());
            
            if (isset($processedTweet)) {
                $processedTweet->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            Log::error("Tweet processing error: " . $e->getMessage(), [
                'tweet_id' => $tweetId ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
