<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

class LLMService
{
    protected $provider;

    public function __construct()
    {
        $this->provider = config('services.llm.provider', 'openai');
    }

    /**
     * Generate a roast for a profile picture
     */
    public function roastProfilePicture($imageData, $username)
    {
        try {
            if ($this->provider === 'openai') {
                return $this->roastWithOpenAI($imageData, $username);
            } elseif ($this->provider === 'gemini') {
                return $this->roastWithGemini($imageData, $username);
            }

            throw new \Exception('Invalid LLM provider configured');
        } catch (\Exception $e) {
            Log::error('Error generating roast: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate roast using OpenAI GPT-4 Vision
     */
    protected function roastWithOpenAI($imageData, $username)
    {
        try {
            // Save image temporarily
            $tempPath = 'temp/' . uniqid() . '.jpg';
            Storage::put($tempPath, $imageData);
            $base64Image = base64_encode($imageData);

            $prompt = $this->getRoastPrompt($username);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a witty and clever roast comedian. Your roasts are funny, creative, and never cross the line into being truly mean or offensive. Keep it light-hearted and entertaining.',
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:image/jpeg;base64,' . $base64Image,
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 280,
                'temperature' => 0.9,
            ]);

            // Clean up temp file
            Storage::delete($tempPath);

            $roast = $response->choices[0]->message->content ?? null;

            if (!$roast) {
                throw new \Exception('No roast generated from OpenAI');
            }

            return $this->formatRoast($roast, $username);
        } catch (\Exception $e) {
            Log::error('OpenAI roast error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate roast using Google Gemini
     */
    protected function roastWithGemini($imageData, $username)
    {
        try {
            $apiKey = config('services.gemini.api_key');
            $base64Image = base64_encode($imageData);

            $prompt = $this->getRoastPrompt($username);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => 'You are a witty and clever roast comedian. Your roasts are funny, creative, and never cross the line into being truly mean or offensive. Keep it light-hearted and entertaining. ' . $prompt,
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => 'image/jpeg',
                                    'data' => $base64Image,
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.9,
                    'maxOutputTokens' => 280,
                ],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Gemini API error: ' . $response->body());
            }

            $data = $response->json();
            $roast = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$roast) {
                throw new \Exception('No roast generated from Gemini');
            }

            return $this->formatRoast($roast, $username);
        } catch (\Exception $e) {
            Log::error('Gemini roast error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the roast prompt
     */
    protected function getRoastPrompt($username)
    {
        return "Look at @{$username}'s profile picture and create a hilarious, witty roast about it. " .
               "Focus on what you see in the image - the pose, expression, style, background, or anything notable. " .
               "Keep it funny and creative but never mean-spirited or offensive. " .
               "The roast should be under 280 characters for Twitter. " .
               "Make it clever and entertaining!";
    }

    /**
     * Format the roast with proper length and structure
     */
    protected function formatRoast($roast, $username)
    {
        // Remove any quotes if present
        $roast = trim($roast, '"\'');
        
        // Ensure it's not too long for Twitter
        if (strlen($roast) > 250) {
            $roast = substr($roast, 0, 247) . '...';
        }

        return $roast;
    }
}
