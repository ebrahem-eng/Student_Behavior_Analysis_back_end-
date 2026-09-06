<?php

namespace App\Services\NLP;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class NLPServiceClient
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = env('ML_SERVICE_URL', 'http://127.0.0.1:8001');
        $this->apiKey = env('ML_SERVICE_API_KEY', 'internal_secret_key_for_sba');
    }

    public function analyzeSentiment(string $text)
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey
            ])->post("{$this->baseUrl}/nlp/sentiment", [
                'text' => $text
            ]);

            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error("Sentiment service error: " . $response->body());
            return null;
        } catch (Exception $e) {
            Log::error("Could not connect to Sentiment service: " . $e->getMessage());
            return null;
        }
    }

    public function askChatbot(string $query, array $context)
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey
            ])->post("{$this->baseUrl}/nlp/chatbot", [
                'query' => $query,
                'context' => $context
            ]);

            if ($response->successful()) {
                return $response->json('response');
            }
            
            Log::error("Chatbot service error: " . $response->body());
            return null;
        } catch (Exception $e) {
            Log::error("Could not connect to Chatbot service: " . $e->getMessage());
            return null;
        }
    }
}
