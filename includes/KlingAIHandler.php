<?php
require_once __DIR__ . '/ApiHandler.php';

/**
 * Kling AI API Handler
 * https://klingai.com/
 */
class KlingAIHandler extends ApiHandler
{
    private $accessKey;
    private $secretKey;
    private $model;

    public function __construct($accessKey = '', $secretKey = '', $model = 'kling-image-v1')
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->model = $model;
    }

    private function makeRequest($url, $data, $headers) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => 0,
                'response' => ''
            ];
        }
        
        return [
            'success' => true,
            'response' => $response,
            'http_code' => $httpCode
        ];
    }

    public function generateImage($prompt, $imageSize = '1024x1024', $numImages = 1)
    {
        $endpoint = 'https://api.klingai.com/v1/images/generations';
        
        $data = [
            'model' => $this->model,
            'prompt' => $prompt,
            'image_size' => $imageSize,
            'num_images' => $numImages
        ];

        $timestamp = (string)time();
        
        // Generate signature - Kling AI uses: HMAC-SHA256(timestamp + accessKey, secretKey)
        $signature = hash_hmac('sha256', $timestamp . $this->accessKey, $this->secretKey);

        $headers = [
            'Content-Type: application/json',
            'X-Access-Key: ' . $this->accessKey,
            'X-Signature: ' . $signature,
            'X-Timestamp: ' . $timestamp
        ];

        $result = $this->makeRequest($endpoint, $data, $headers);

        // Debug: Log the response for troubleshooting
        error_log("Kling AI Response: " . $result['response']);

        if (!$result['success']) {
            return "Kling AI Connection Error: " . $result['error'];
        }

        // Check if response is HTML (error page)
        if (strip_tags($result['response']) !== $result['response']) {
            return "Kling AI API Error (Server Error): The API may be unavailable. Please try again later or use another provider like Pollinations or Stability AI.";
        }

        $responseData = json_decode($result['response'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Kling AI Parse Error: " . substr($result['response'], 0, 300);
        }

        if ($result['http_code'] !== 200) {
            $msg = $responseData['message'] ?? $responseData['error'] ?? 'Unknown error';
            return "Kling AI HTTP Error {$result['http_code']}: {$msg}";
        }

        // Extract image URLs
        if (isset($responseData['data']['images'])) {
            $images = [];
            foreach ($responseData['data']['images'] as $img) {
                $images[] = $img['url'];
            }
            return implode("\n", $images);
        }

        return "Kling AI: No images in response. Response: " . json_encode($responseData);
    }

    public function generateVideo($prompt, $duration = 5)
    {
        $endpoint = 'https://api.klingai.com/v1/videos/generations';
        
        $data = [
            'model' => 'kling-v1-5',
            'prompt' => $prompt,
            'duration' => $duration
        ];

        $timestamp = (string)time();
        $signature = hash_hmac('sha256', $timestamp . $this->accessKey, $this->secretKey);

        $headers = [
            'Content-Type: application/json',
            'X-Access-Key: ' . $this->accessKey,
            'X-Signature: ' . $signature,
            'X-Timestamp: ' . $timestamp
        ];

        $result = $this->makeRequest($endpoint, $data, $headers);

        // Debug: Log the response
        error_log("Kling AI Video Response: " . $result['response']);

        if (!$result['success']) {
            return "Kling AI Connection Error: " . $result['error'];
        }

        // Check if response is HTML
        if (strip_tags($result['response']) !== $result['response']) {
            return "Kling AI API Error (Server Error): The API may be unavailable. Please try Pollinations Video for instant results.";
        }

        $responseData = json_decode($result['response'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Kling AI Parse Error: " . substr($result['response'], 0, 300);
        }

        if ($result['http_code'] !== 200) {
            $msg = $responseData['message'] ?? $responseData['error'] ?? 'Unknown error';
            return "Kling AI HTTP Error {$result['http_code']}: {$msg}";
        }

        if (isset($responseData['data']['task_id'])) {
            return "Video generation started! Task ID: " . $responseData['data']['task_id'] . "\n\nNote: Video generation takes 2-5 minutes. Use Pollinations Video for instant results.";
        }

        return "Kling AI: No video in response. Response: " . json_encode($responseData);
    }

    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        return $this->generateImage($userText);
    }
}
