# ?? Roast This DP - Twitter Profile Picture Roasting Bot

A Laravel-based Twitter bot that roasts profile pictures using AI! Tag `@roast_this_dp` with any Twitter user, and the bot will analyze their profile picture and generate a witty, humorous roast.

## ? Features

- **Automated Mention Processing**: Monitors Twitter mentions every 5 minutes
- **AI-Powered Roasts**: Uses OpenAI GPT-4o or Google Gemini to generate clever roasts
- **Profile Picture Analysis**: Downloads and analyzes target user's profile picture
- **Smart Replies**: Automatically replies to the original tweet with the roast
- **Duplicate Prevention**: Tracks processed tweets to avoid repeating roasts
- **Error Handling**: Comprehensive logging and error management
- **Database Tracking**: Stores all processed requests with status tracking

## ?? Requirements

- PHP 8.3 or higher
- Composer
- MySQL/PostgreSQL/SQLite database
- Twitter Developer Account with API v2 access
- OpenAI API key OR Google Gemini API key

## ?? Installation

### 1. Clone the Repository

```bash
cd /workspace/twitter-roast-bot
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Set Up Environment Variables

Copy the `.env` file and update it with your credentials:

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure API Credentials

Edit your `.env` file and add the following credentials:

#### Twitter API Credentials

You'll need to create a Twitter Developer account and generate these credentials:

```env
TWITTER_API_KEY=your_twitter_api_key_here
TWITTER_API_SECRET=your_twitter_api_secret_here
TWITTER_ACCESS_TOKEN=your_twitter_access_token_here
TWITTER_ACCESS_TOKEN_SECRET=your_twitter_access_token_secret_here
TWITTER_BEARER_TOKEN=your_twitter_bearer_token_here
TWITTER_BOT_USERNAME=roast_this_dp
```

**How to get Twitter API credentials:**
1. Go to [Twitter Developer Portal](https://developer.twitter.com/en/portal/dashboard)
2. Create a new Project and App
3. Enable OAuth 1.0a with Read and Write permissions
4. Generate API Keys and Access Tokens
5. Copy all credentials to your `.env` file

#### OpenAI API Configuration (Option 1)

```env
OPENAI_API_KEY=your_openai_api_key_here
LLM_PROVIDER=openai
```

**How to get OpenAI API key:**
1. Go to [OpenAI Platform](https://platform.openai.com/)
2. Sign up or log in
3. Navigate to API Keys section
4. Create a new API key
5. Copy it to your `.env` file

#### Google Gemini API Configuration (Option 2)

```env
GEMINI_API_KEY=your_gemini_api_key_here
LLM_PROVIDER=gemini
```

**How to get Gemini API key:**
1. Go to [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Sign in with your Google account
3. Create a new API key
4. Copy it to your `.env` file

### 5. Run Database Migrations

```bash
php artisan migrate
```

### 6. Test the Configuration

Test that your Twitter API credentials work:

```bash
php artisan twitter:process-mentions
```

## ?? Usage

### Manual Execution

You can manually process mentions at any time:

```bash
php artisan twitter:process-mentions
```

### Automated Processing with Scheduler

The bot is configured to automatically check for new mentions every 5 minutes.

**For Development:**

```bash
php artisan schedule:work
```

**For Production (using Cron):**

Add this cron entry to your server:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## ?? How It Works

### User Interaction

1. A Twitter user mentions your bot with a target user:
   ```
   @roast_this_dp @target_user
   ```

2. The bot:
   - Fetches the mention
   - Extracts the target username
   - Downloads the target's profile picture
   - Sends it to AI (OpenAI or Gemini) with a roasting prompt
   - Receives a witty roast
   - Posts a reply tagging both users

3. Example reply:
   ```
   @requester @target_user That pose screams "I peaked in 2015" 
   but the filter quality says you're still trying! ????
   ```

### Technical Flow

```
???????????????????
?  New Mention    ?
?  @roast_this_dp ?
???????????????????
         ?
         ?
???????????????????
? Parse Username  ?
? & Validate      ?
???????????????????
         ?
         ?
???????????????????
?  Fetch Profile  ?
?  Picture        ?
???????????????????
         ?
         ?
???????????????????
?  Send to LLM    ?
?  (GPT/Gemini)   ?
???????????????????
         ?
         ?
???????????????????
?  Generate       ?
?  Roast          ?
???????????????????
         ?
         ?
???????????????????
?  Post Reply     ?
?  Tweet          ?
???????????????????
```

## ??? Database Schema

### `processed_tweets` Table

| Column              | Type      | Description                          |
|---------------------|-----------|--------------------------------------|
| id                  | bigint    | Primary key                          |
| tweet_id            | string    | Twitter tweet ID (unique)            |
| requester_username  | string    | User who requested the roast         |
| target_username     | string    | User whose DP is being roasted       |
| roast_text          | text      | Generated roast content              |
| reply_tweet_id      | string    | ID of the reply tweet                |
| status              | string    | pending/processing/completed/failed  |
| error_message       | text      | Error details if failed              |
| created_at          | timestamp | When the request was received        |
| updated_at          | timestamp | Last update time                     |

## ?? Project Structure

```
twitter-roast-bot/
??? app/
?   ??? Console/
?   ?   ??? Commands/
?   ?       ??? ProcessTwitterMentions.php  # Main bot command
?   ??? Models/
?   ?   ??? ProcessedTweet.php              # Database model
?   ??? Services/
?       ??? TwitterService.php              # Twitter API integration
?       ??? LLMService.php                  # OpenAI/Gemini integration
??? config/
?   ??? services.php                        # API configuration
?   ??? openai.php                          # OpenAI config
??? database/
?   ??? migrations/
?       ??? *_create_processed_tweets_table.php
??? routes/
?   ??? console.php                         # Scheduler configuration
??? .env                                    # Environment variables
```

## ??? Configuration

### Changing the Bot Username

Update the `TWITTER_BOT_USERNAME` in your `.env` file:

```env
TWITTER_BOT_USERNAME=your_bot_username
```

### Adjusting Processing Frequency

Edit `routes/console.php` to change the schedule:

```php
// Every 5 minutes (default)
Schedule::command('twitter:process-mentions')->everyFiveMinutes();

// Every minute
Schedule::command('twitter:process-mentions')->everyMinute();

// Every 10 minutes
Schedule::command('twitter:process-mentions')->everyTenMinutes();
```

### Switching LLM Providers

Change the provider in `.env`:

```env
# Use OpenAI
LLM_PROVIDER=openai

# Use Google Gemini
LLM_PROVIDER=gemini
```

### Customizing the Roast Style

Edit the prompt in `app/Services/LLMService.php`:

```php
protected function getRoastPrompt($username)
{
    return "Your custom roasting prompt here...";
}
```

## ?? Security Best Practices

1. **Never commit `.env` file** - It contains sensitive API keys
2. **Use environment variables** - Store all credentials in `.env`
3. **Rate limiting** - Twitter API has rate limits; the bot handles this
4. **Error handling** - All errors are logged for monitoring
5. **Database backups** - Regularly backup your processed tweets database

## ?? Monitoring

### View Logs

```bash
tail -f storage/logs/laravel.log
```

### Check Processed Tweets

```bash
php artisan tinker
```

Then run:

```php
\App\Models\ProcessedTweet::latest()->limit(10)->get();
\App\Models\ProcessedTweet::where('status', 'failed')->get();
```

### Database Statistics

```bash
php artisan tinker
```

```php
// Total processed
\App\Models\ProcessedTweet::count();

// Successful roasts
\App\Models\ProcessedTweet::where('status', 'completed')->count();

// Failed attempts
\App\Models\ProcessedTweet::where('status', 'failed')->count();
```

## ?? Troubleshooting

### "Twitter API error"

- Check your Twitter API credentials in `.env`
- Ensure your app has Read and Write permissions
- Verify your Bearer Token is correct

### "OpenAI/Gemini API error"

- Check your API key is valid
- Ensure you have available credits
- Verify the `LLM_PROVIDER` setting matches your configured API

### "No new mentions found"

- Ensure your bot account exists on Twitter
- Check that the bot username in `.env` matches your Twitter handle
- Verify someone has actually mentioned your bot

### Scheduler not running

- Make sure you're running `php artisan schedule:work` (dev) or have set up the cron job (production)
- Check Laravel logs for any errors

## ?? Example Roasts

Here are some examples of what the bot might generate:

> "That selfie angle is working overtime to hide something, and we all know what it is! ??"

> "Your profile pic has 'I'm fun at parties' energy, but the empty room in the background tells a different story ??"

> "The blur filter is doing more heavy lifting than a crane operator ???"

## ?? Development

### Running Tests

```bash
php artisan test
```

### Code Style

```bash
./vendor/bin/pint
```

## ?? Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## ?? License

This project is open-sourced software licensed under the MIT license.

## ?? Disclaimer

This bot is for entertainment purposes only. The roasts are AI-generated and meant to be humorous and light-hearted. Please use responsibly and be mindful of Twitter's Terms of Service and community guidelines.

## ?? Support

For issues and questions:
- Check the logs: `storage/logs/laravel.log`
- Review the troubleshooting section above
- Check Twitter API status: https://api.twitterstat.us/

## ?? Roadmap

Future enhancements:
- [ ] Image quality analysis
- [ ] Multiple image support
- [ ] Roast style preferences
- [ ] Rate limiting per user
- [ ] Analytics dashboard
- [ ] Webhook support for real-time processing

---

Made with ?? and ?? by the Laravel community
