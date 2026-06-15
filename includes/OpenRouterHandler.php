<?php
require_once __DIR__ . '/ApiHandler.php';

/**
 * OpenRouter API Handler
 */
class OpenRouterHandler extends ApiHandler
{

    public function generateResponse($userText, $imageBase64 = null, $imageMimeType = 'image/jpeg')
    {
        $model = $this->model ?: 'deepseek/deepseek-r1';
        $url = 'https://openrouter.ai/api/v1/chat/completions';

        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $userText]
            ]
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'HTTP-Referer: http://localhost',
            'X-Title: Chat Application'
        ];

        $result = $this->makeRequest($url, $data, $headers);

        if (!$result['success']) {
            return $this->handleError('OpenRouter', $result['error']);
        }

        $responseData = json_decode($result['response'], true);

        if ($result['http_code'] !== 200) {
            return $this->handleError('OpenRouter', $responseData['error'] ?? 'Failed to fetch response');
        }

        // Extract response text
        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }

        return $this->handleError('OpenRouter', 'Empty response from API');
    }
}
