<?php

namespace App\Services\Prediction;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PredictionServiceClient
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = env('ML_SERVICE_URL', 'http://127.0.0.1:8001');
        $this->apiKey = env('ML_SERVICE_API_KEY', 'internal_secret_key_for_sba');
    }

    /**
     * Get a risk prediction for a student.
     */
    public function predict(int $studentId, array $features)
    {
        $payload = array_merge(['student_id' => $studentId], $features);
        
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey
            ])->post("{$this->baseUrl}/predict", $payload);

            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error("Prediction service error: " . $response->body());
            return null;
        } catch (Exception $e) {
            Log::error("Could not connect to Prediction service: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get SHAP explanations for a student's prediction.
     */
    public function explain(int $studentId, array $features)
    {
        $payload = array_merge(['student_id' => $studentId], $features);
        
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey
            ])->post("{$this->baseUrl}/explain", $payload);

            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error("Explanation service error: " . $response->body());
            return null;
        } catch (Exception $e) {
            Log::error("Could not connect to Explanation service: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Trigger a retraining job on the ML microservice.
     */
    public function retrain()
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey
            ])->post("{$this->baseUrl}/retrain");

            return $response->successful();
        } catch (Exception $e) {
            Log::error("Could not trigger retraining: " . $e->getMessage());
            return false;
        }
    }
}
