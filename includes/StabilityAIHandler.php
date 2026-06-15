<?php

class StabilityAIHandler
{
    private $apiKey;
    private $model;
    private $apiUrl = 'https://api.stability.ai/v1/generation';

    public function __construct($apiKey, $model = 'stable-diffusion-xl-1024-v1-0')
    {
        if (is_array($apiKey)) {
            $apiKey = $apiKey[array_rand($apiKey)];
        }
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function generateResponse($prompt)
    {
        if (empty($this->apiKey)) {
            return "Error: Stability AI API key is missing. Please configure it in settings.";
        }

        $engineId = 'stable-diffusion-xl-1024-v1-0';
        $url = "{$this->apiUrl}/{$engineId}/text-to-image";

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $data = [
            'text_prompts' => [
                [
                    'text' => $prompt,
                    'weight' => 1
                ]
            ],
            'cfg_scale' => 7,
            'height' => 1024,
            'width' => 1024,
            'samples' => 1,
            'steps' => 30,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return "Error sending request to Stability AI: $curlError";
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            if (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } elseif (isset($errorData['errors']) && is_array($errorData['errors'])) {
                $errorMessage = $errorData['errors'][0]['message'] ?? 'Unknown error';
            } else {
                $errorMessage = "HTTP $httpCode - " . substr($response, 0, 200);
            }
            return "Error from Stability AI (Code $httpCode): $errorMessage";
        }

        $result = json_decode($response, true);

        $base64Image = null;
        
        if (isset($result['artifacts'][0]['base64'])) {
            $base64Image = $result['artifacts'][0]['base64'];
        } elseif (isset($result['images'][0]['base64'])) {
            $base64Image = $result['images'][0]['base64'];
        } elseif (isset($result['image'])) {
            $base64Image = $result['image'];
        }

        if (!$base64Image) {
            return "Error: Unexpected response from Stability AI. The API may have changed.";
        }

        $uploadsDir = __DIR__ . '/../uploads/stability';
        if (!file_exists($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $filename = 'stability_' . time() . '_' . uniqid() . '.png';
        $filepath = $uploadsDir . '/' . $filename;

        $imageData = base64_decode($base64Image);
        file_put_contents($filepath, $imageData);

        $imageUrl = 'uploads/stability/' . $filename;
        return "![Generated Image]($imageUrl)";
    }
}
?>