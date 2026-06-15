<?php
require_once __DIR__ . '/ApiHandler.php';

/**
 * SambaNova API Handler
 */
class SambaNovaHandler extends ApiHandler
{

    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        $model = $this->model ?: 'Meta-Llama-3.3-70B-Instruct';
        $url = 'https://api.sambanova.ai/v1/chat/completions';

        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $userText]
            ],
            'stream' => false
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $result = $this->makeRequest($url, $data, $headers);

        if (!$result['success']) {
            return $this->handleError('SambaNova', $result['error']);
        }

        $responseData = json_decode($result['response'], true);

        if ($result['http_code'] !== 200) {
            return $this->handleError('SambaNova', $responseData['error'] ?? 'Failed to fetch response');
        }

        // Extract response text
        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }

        return $this->handleError('SambaNova', 'Empty response from API');
    }
}
