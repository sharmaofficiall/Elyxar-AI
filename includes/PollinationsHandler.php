<?php
require_once __DIR__ . '/ApiHandler.php';

/**
 * Pollinations AI Handler
 * Supports text, image, and video generation
 */
class PollinationsHandler extends ApiHandler
{
    private $type; // 'text', 'image', or 'video'

    public function __construct($apiKey = '', $model = '', $type = 'text')
    {
        parent::__construct($apiKey, $model);
        $this->type = $type;
    }

    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        if ($this->type === 'image') {
            return $this->generateImage($userText);
        } elseif ($this->type === 'video') {
            return $this->generateVideo($userText);
        } else {
            return $this->generateText($userText, $imageBase64, $imageMimeType);
        }
    }

    private function generateText($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        $model = $this->model ?: 'openai';
        $url = 'https://text.pollinations.ai/openai';

        $messages = [];

        // Handle image input for vision
        if ($imageBase64 && $imageMimeType) {
            $messages[] = [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $userText
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => "data:{$imageMimeType};base64,{$imageBase64}"
                        ]
                    ]
                ]
            ];
        } else {
            $messages[] = [
                'role' => 'user',
                'content' => $userText
            ];
        }

        $data = [
            'messages' => $messages,
            'model' => $model
        ];

        $headers = ['Content-Type: application/json'];

        $result = $this->makeRequest($url, $data, $headers);

        if (!$result['success']) {
            return $this->handleError('Pollinations', $result['error']);
        }

        $responseData = json_decode($result['response'], true);

        if ($result['http_code'] !== 200) {
            return $this->handleError('Pollinations', $responseData['error'] ?? 'Failed to fetch response');
        }

        // Extract response text
        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }

        return $result['response'];
    }

    private function generateImage($prompt)
    {
        $model = $this->model ?: 'flux';
        $encodedPrompt = urlencode($prompt);
        // Add some parameters for better quality
        $width = isset($_GET['width']) ? $_GET['width'] : '1024';
        $height = isset($_GET['height']) ? $_GET['height'] : '1024';
        return "https://image.pollinations.ai/prompt/{$encodedPrompt}?width={$width}&height={$height}&model={$model}&nologo=true";
    }

    private function generateVideo($prompt)
    {
        $model = $this->model ?: 'veo';
        
        $modelParams = [
            'veo' => 'veo',
            'seedance' => 'seedance',
            'wan' => 'wan',
            'ltx-2' => 'ltx-2'
        ];
        
        $modelParam = $modelParams[$model] ?? 'veo';
        $promptEncoded = rawurlencode($prompt);
        
        $apiKey = !empty($this->apiKey) ? $this->apiKey : '';
        
        if (empty($apiKey)) {
            return "Video generation requires API key. Please add your Pollinations API key in config/config.php. Get your free key at https://enter.pollinations.ai";
        }
        
        $url = "https://gen.pollinations.ai/image/" . $promptEncoded . "?model=" . $modelParam . "&video=true";
        
        $headers = ['Authorization: Bearer ' . $apiKey];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $videoData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            return "Error: $curlError";
        }
        
        if ($httpCode === 401) {
            return "Error: Invalid API key. Please check your Pollinations API key.";
        }
        
        if ($httpCode === 402) {
            return "Error: Video generation requires paid credits. Please purchase pollen credits at https://enter.pollinations.ai/pricing";
        }
        
        if ($httpCode !== 200) {
            return "Error: Video generation failed (HTTP $httpCode)";
        }
        
        if (strpos($videoData, '<') === 0) {
            return "Error: Server returned HTML instead of video. Please try a different model.";
        }
        
        $uploadsDir = __DIR__ . '/../uploads/videos';
        if (!file_exists($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        $filename = 'video_' . time() . '_' . uniqid() . '.mp4';
        $filepath = $uploadsDir . '/' . $filename;
        
        if (file_put_contents($filepath, $videoData) === false) {
            return "Error: Failed to save video";
        }
        
        $videoUrl = 'uploads/videos/' . $filename;
        return $videoUrl;
    }
}
