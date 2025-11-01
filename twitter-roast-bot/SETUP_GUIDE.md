# ?? Quick Setup Guide - Roast This DP Bot

## Step-by-Step Setup Instructions

### 1. Twitter Developer Account Setup

1. Go to [https://developer.twitter.com/en/portal/dashboard](https://developer.twitter.com/en/portal/dashboard)
2. Sign in with your Twitter account
3. Click "Create Project"
4. Fill in project details:
   - Project Name: "Roast This DP Bot"
   - Use Case: "Making a bot"
   - Description: "A bot that roasts profile pictures"
5. Create an App within the project
6. In the app settings:
   - Go to "Keys and Tokens" tab
   - Generate and save:
     - API Key and Secret
     - Access Token and Secret
     - Bearer Token
7. Go to "Settings" tab:
   - Change "App permissions" to "Read and Write"
   - Save changes
   - Regenerate tokens if prompted

### 2. OpenAI API Setup (Option 1)

1. Go to [https://platform.openai.com/](https://platform.openai.com/)
2. Sign up or log in
3. Add billing information (required for API access)
4. Go to [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys)
5. Click "Create new secret key"
6. Name it "Roast Bot" and save the key

**Note:** You'll need GPT-4o access. Make sure you have credits in your account.

### 3. Google Gemini API Setup (Option 2 - Alternative to OpenAI)

1. Go to [https://makersuite.google.com/app/apikey](https://makersuite.google.com/app/apikey)
2. Sign in with your Google account
3. Click "Create API Key"
4. Select or create a project
5. Copy the generated API key

### 4. Configure Environment Variables

Edit `/workspace/twitter-roast-bot/.env`:

```env
# Twitter Configuration
TWITTER_API_KEY=paste_your_api_key_here
TWITTER_API_SECRET=paste_your_api_secret_here
TWITTER_ACCESS_TOKEN=paste_your_access_token_here
TWITTER_ACCESS_TOKEN_SECRET=paste_your_access_token_secret_here
TWITTER_BEARER_TOKEN=paste_your_bearer_token_here
TWITTER_BOT_USERNAME=your_bot_twitter_handle

# Choose LLM Provider (openai or gemini)
LLM_PROVIDER=openai

# If using OpenAI:
OPENAI_API_KEY=paste_your_openai_key_here

# If using Gemini:
GEMINI_API_KEY=paste_your_gemini_key_here
```

### 5. Run Database Migrations

```bash
cd /workspace/twitter-roast-bot
php artisan migrate
```

### 6. Test the Bot

```bash
php artisan twitter:process-mentions
```

You should see:
```
Starting to process Twitter mentions...
No new mentions found.
Finished processing mentions.
```

### 7. Start the Scheduler (Development)

```bash
php artisan schedule:work
```

This will run the bot every 5 minutes automatically.

### 8. Production Deployment

For production, add this to your crontab:

```bash
crontab -e
```

Add this line:
```
* * * * * cd /workspace/twitter-roast-bot && php artisan schedule:run >> /dev/null 2>&1
```

## Testing Your Bot

1. Create a test tweet mentioning your bot:
   ```
   @your_bot_username @some_user
   ```

2. Wait up to 5 minutes or run manually:
   ```bash
   php artisan twitter:process-mentions
   ```

3. Check the logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Common Issues

### Issue: "Twitter API authentication failed"
**Solution:** Double-check your Twitter API credentials. Make sure they're copied correctly without extra spaces.

### Issue: "App does not have write permissions"
**Solution:** Go to your Twitter app settings and change permissions to "Read and Write", then regenerate your tokens.

### Issue: "OpenAI API error"
**Solution:** Verify you have:
- Valid API key
- Added payment method
- Available credits
- Access to GPT-4o

### Issue: "No mentions found"
**Solution:** 
- Verify your bot's Twitter username matches `TWITTER_BOT_USERNAME` in `.env`
- Make sure someone actually mentioned your bot
- Check that your Bearer Token is correct

## Next Steps

1. Test with a few mentions
2. Monitor the logs for any errors
3. Adjust the roast prompt if needed (in `app/Services/LLMService.php`)
4. Set up monitoring for production

## Need Help?

Check the main README.md for detailed documentation and troubleshooting.
