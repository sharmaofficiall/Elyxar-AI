<?php
require_once __DIR__ . '/ApiHandler.php';

class NvidiaHandler extends ApiHandler
{
    private $baseUrl = 'https://integrate.api.nvidia.com/v1';

    public function __construct($apiKey = '', $model = '')
    {
        parent::__construct($apiKey, $model);
    }

    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        $model = $this->model ?: 'nvidia/nemotron-mini-4b-instruct';

        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $userText]
            ],
            'temperature' => 0.5,
            'max_tokens' => 4096
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $endpoint = $this->baseUrl . '/chat/completions';
        $result = $this->makeRequest($endpoint, $data, $headers);

        if (!$result['success']) {
            return $this->handleError('NVIDIA', $result['error']);
        }

        $responseData = json_decode($result['response'], true);

        if ($result['http_code'] !== 200) {
            return $this->handleError('NVIDIA', $responseData['message'] ?? $responseData['error'] ?? 'Failed to fetch response');
        }

        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }

        return $this->handleError('NVIDIA', 'Empty response from API');
    }
}