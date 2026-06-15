<?php
require_once __DIR__ . '/ApiHandler.php';

/**
 * Groq API Handler
 */
class GroqHandler extends ApiHandler
{
    public function __construct($apiKey = '', $model = '')
    {
        parent::__construct($apiKey, $model);
    }

    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        $model = $this->model ?: 'llama3-8b-8192'; // Default Groq model

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

        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
        $result = $this->makeRequest($endpoint, $data, $headers);

        if (!$result['success']) {
            return $this->handleError('Groq', $result['error']);
        }

        $responseData = json_decode($result['response'], true);

        if ($result['http_code'] !== 200) {
            return $this->handleError('Groq', $responseData['error'] ?? 'Failed to fetch response');
        }

        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }

        return $this->handleError('Groq', 'Empty response from API');
    }
}
