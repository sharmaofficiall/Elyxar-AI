<?php
require_once __DIR__ . '/ApiHandler.php';

/**
 * DeepSeek API Handler
 * Compatible with OpenAI API format
 */
class DeepSeekHandler extends ApiHandler
{
    private $endpoint = 'https://api.deepseek.com/chat/completions';
    private $providerName = 'DeepSeek';

    public function __construct($apiKey = '', $model = 'deepseek-chat')
    {
        // If apiKey is an array, pick a random one
        if (is_array($apiKey)) {
            $apiKey = $apiKey[array_rand($apiKey)];
        }
        parent::__construct($apiKey, $model);
    }

    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        $model = $this->model ?: 'deepseek-chat';

        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $userText]
            ],
            'stream' => false
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $result = $this->makeRequest($this->endpoint, $data, $headers);

        if (!$result['success']) {
            return $this->handleError($this->providerName, $result['error']);
        }

        $responseData = json_decode($result['response'], true);

        if ($result['http_code'] !== 200) {
            return $this->handleError($this->providerName, $responseData['error'] ?? 'Failed to fetch response');
        }

        // Extract response text
        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }

        return $this->handleError($this->providerName, 'Empty response from API');
    }
}
